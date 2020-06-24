<?php

/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");

/**
 * User interface hook class
 *
 * @author Alexander Killing <killing@leifos.de>
 */
class ilFeverCurvesUIHookGUI extends ilUIHookPluginGUI
{
    static protected $rendered = false;

    /**
     * Modify HTML output of GUI elements. Modifications modes are:
     * - ilUIHookPluginGUI::KEEP (No modification)
     * - ilUIHookPluginGUI::REPLACE (Replace default HTML with your HTML)
     * - ilUIHookPluginGUI::APPEND (Append your HTML to the default HTML)
     * - ilUIHookPluginGUI::PREPEND (Prepend your HTML to the default HTML)
     *
     * @param string $a_comp component
     * @param string $a_part string that identifies the part of the UI that is handled
     * @param string $a_par array of parameters (depend on $a_comp and $a_part)
     *
     * @return array array with entries "mode" => modification mode, "html" => your html
     */
    function getHTML($a_comp, $a_part, $a_par = array())
    {
        global $DIC;

        $ctrl = $DIC->ctrl();

        // Skill UI Hook Plugin allows to modify the skill list
        if ($a_comp == "Services/Skill"
            && $a_part == "personal_skill_html"
            && $ctrl->getContextObjType() == "crs")
        {

            if ($_GET["pluginCmd"] == "applyFilter") {
                $this->applyFilter();
            }

            $this->from = ilSession::get("skmg_pf_from");
            $this->to = ilSession::get("skmg_pf_to");


            $tpl = $this->getPluginObject()->getTemplate("tpl.fever_skill.html");

            if (!self::$rendered) {

                $tpl->setVariable("TOOLBAR", $this->renderToolbar());

                $tpl->touchBlock("style_patch");
                $tpl->setVariable("FEVER_CHART", $this->renderFeverChart($a_par));
                self::$rendered = true;
            }

            $standard_skill_list_html = $a_par["personal_skills_gui"]->renderSkillHTML($a_par["top_skill_id"], $a_par["user_id"],
                $a_par["edit"], $a_par["tref_id"]);

            $tpl->setVariable("SKILL_LIST", $standard_skill_list_html);

            return array("mode" => ilUIHookPluginGUI::REPLACE, "html" => $tpl->get());
        }

        return array("mode" => ilUIHookPluginGUI::KEEP, "html" => "");
    }

    /**
     * Get fever chart
     * @return string
     */
    protected function renderFeverChart($a_par)
    {
        global $DIC;

        $ilUser = $DIC->user();
        $lng = $DIC->language();

        //return "FEVER CHART";
        $this->getPluginObject()->includeClass("class.ilLineVerticalChart.php");
        $this->getPluginObject()->includeClass("class.ilLineVerticalChartData.php");
        $this->getPluginObject()->includeClass("class.ilLineVerticalChartDataScatter.php");
        $this->getPluginObject()->includeClass("class.ilLineVerticalChartScatter.php");



        //if ($a_skills == null) {
        $a_skills = $a_par["personal_skills_gui"]->obj_skills;
        //}

        //if ($a_user_id == 0) {
        $user_id = $ilUser->getId();
        //} else {
        //    $user_id = $a_user_id;
        //}


        // competences with target level
        $skills = array();
        if ($a_par["personal_skills_gui"]->getProfileId() > 0) {
            $profile = new ilSkillProfile($a_par["personal_skills_gui"]->getProfileId());
            $profile_levels = $profile->getSkillLevels();

            foreach ($profile_levels as $l) {
                $skills[] = array(
                    "base_skill_id" => $l["base_skill_id"],
                    "tref_id" => $l["tref_id"],
                    "level_id" => $l["level_id"]
                );
            }
        } elseif (is_array($a_skills)) {
            $skills = $a_skills;
        }



        // output chart stuff
        $scatter_chart_html = "";


        $comp_labels = array();
        $level_labels = array();

        $all_numbers = array();
        $all_types = array();
        $all_dates = array();

        foreach ($skills as $k => $l) {

            $bs = new ilBasicSkill($l["base_skill_id"]);

            // competence labels for y-axis
            $comp_labels[] = ilBasicSkill::_lookupTitle($l["base_skill_id"], $l["tref_id"]);

            // possible competence levels for x-axis
            $levels = $bs->getLevelData();


            // get all object (course) triggered entries
            $level_entries = array();
            foreach ($bs->getAllHistoricLevelEntriesOfUser($l["tref_id"], $user_id,
                ilBasicSkill::EVAL_BY_ALL) as $entry) {
                if (count($a_par["personal_skills_gui"]->getTriggerObjectsFilter()) && !in_array($entry['trigger_obj_id'],
                        $a_par["personal_skills_gui"]->getTriggerObjectsFilter())) {
                    continue;
                }

                if ($a_par["personal_skills_gui"]->getFilter()->isInRange($levels, $entry)) {
                    $level_entries[] = $entry;
                }
            }


            $cnt = 0;
            foreach ($levels as $lv) {
                $cnt++;
                if ($l["level_id"] == $lv["id"]) {
                    $skills[$k]["target_cnt"] = $cnt; //von cnt zu nr ändern
                }

                if (!in_array($lv["title"], $level_labels)) {
                    $level_labels[] = $lv["title"]; //anders lösen, siehe oben(?)
                }
            }

            $numbers = array();
            $types = array();
            foreach ($level_entries as $i => $entry) {
                $num_data = $bs->getLevelData($entry["level_id"]);
                $num = (float) $num_data["nr"];
                $num = $num + (float) $entry["next_level_fulfilment"];
                $numbers[
                    $entry["status_date"] . "_" . $entry["trigger_obj_id"] . "_" . $entry["self_eval"]
                ] = $num;
        //die Arrays umändern, die Keys sollen einzelne Einträge in extra Array werden(?)
                $types[
                    $entry["status_date"] . "_" . $entry["trigger_obj_id"] . "_" . $entry["self_eval"]
                ] = $entry["trigger_title"];

                if (!in_array(
                    $entry["status_date"] . "_" . $entry["trigger_obj_id"] . "_" . $entry["self_eval"],
                    $all_dates
                    )
                ) {
                    $all_dates[] = $entry["status_date"] . "_" . $entry["trigger_obj_id"] . "_" . $entry["self_eval"];
                }
            }
            $all_numbers[$l["base_skill_id"] . "_" . $l["tref_id"]] = $numbers;
            $all_types[$l["base_skill_id"] . "_" . $l["tref_id"]] = $types;
        }


        $scatter_chart = new ilLineVerticalChartScatter("fever_curves", $this->getPluginObject());
        $scatter_chart->setYAxisMax(sizeof($comp_labels) - 1); //  eleganteren Weg finden?
        $scatter_chart->setXAxisMax(sizeof($level_labels) - 1); // eleganteren Weg finden?
        $scatter_chart->setYAxisLabels($comp_labels);
        $scatter_chart->setXAxisLabels($level_labels);

        // target level
        $scatter_data_target = new ilLineVerticalChartDataScatter();
        $scatter_data_target->setLabel($lng->txt("skmg_target_level"));
        //$scatter_data_target->setColor("green"); // change to hex code


        // fill in data for target level and add to chart
        $cnt = 0;
        foreach ($skills as $pl) {
            $scatter_data_target->addPoint(((int) $pl["target_cnt"] - 1), $cnt);
            $cnt++;
        }

        if ($a_par["personal_skills_gui"]->getProfileId() > 0) {
            $scatter_chart->addData($scatter_data_target);
        }


        //
        $line_numbers = array();
        $new_types = array();
        foreach ($all_dates as $date) {
            $new_types[$date] = array_column($all_types, $date);

            foreach ($all_numbers as $skill => $numbers) {
                if (!array_key_exists($date, $numbers)) {
                    $all_numbers[$skill][$date] = 0;
                }
            }
            $line_numbers[$date] = array_combine(array_keys($all_numbers), array_column($all_numbers, $date));
        }


        // fill in data for source object levels and add to chart
        $count = 0;
        foreach ($line_numbers as $i => $line) {
            $title_label = $new_types[$i][0];
            $date_label = substr($i, 0, strpos($i, '_'));

            $scatter_data_source_entry = new ilLineVerticalChartDataScatter();
            $scatter_data_source_entry->setLabel(
                $title_label . " (" . date("d.m.y", strtotime($date_label)) . ")"
            );
            //$scatter_data_source_entry->setColor("red"); // change to hex code

            $c = 0;
            foreach ($line as $point) {
                if ($point != 0) {
                    $scatter_data_source_entry->addPoint((float) $point - 1, $c);
                }
                $c++;
            }
            $scatter_chart->addData($scatter_data_source_entry);
            $count++;
        }



        $scatter_chart_html = $scatter_chart->getHTML();

        $pan = ilPanelGUI::getInstance();
        $pan->setPanelStyle(ilPanelGUI::PANEL_STYLE_PRIMARY);
        $pan->setBody($scatter_chart_html);
        $scatter_chart_html = $pan->getHTML();

        return $scatter_chart_html;
    }

    /**
     * Modify toolbar
     * @param
     * @return
     */
    protected function renderToolbar()
    {
        global $DIC;

        $original_toolbar = $DIC->toolbar();
        $ctrl = $DIC->ctrl();

        $toolbar = new ilToolbarGUI();
        $toolbar->setId("SkillFilter");
        $ctrl->setParameterByClass("ilcontskillpresentationgui", "pluginCmd", "applyFilter");
        $toolbar->setFormAction($ctrl->getFormActionByClass("ilcontskillpresentationgui"));
        $ctrl->setParameterByClass("ilcontskillpresentationgui", "pluginCmd", "");
        foreach ($original_toolbar->getItems() as $item) {
            switch ($item["type"]) {
                case "input":
                    $toolbar->addInputItem($item["input"], true);
                    break;

                case "fbutton":
                    // we ignore the select profile button for now
                    break;

                default:
                    break;
            }
        }

        // this hides the original toolbar
        $original_toolbar->setItems([]);

        $lng = $DIC->language();

        // from
        $from = new ilDateTimeInputGUI($lng->txt("from"), "from");
        if ($this->from != "") {
            $from->setDate(new ilDateTime($this->from, IL_CAL_DATETIME));
        }
        $toolbar->addInputItem($from, true);

        // to
        $to = new ilDateTimeInputGUI($lng->txt("to"), "to");
        if ($this->to != "") {
            $to->setDate(new ilDateTime($this->to, IL_CAL_DATETIME));
        }
        $toolbar->addInputItem($to, true);

        // button
        $toolbar->addFormButton(
            $lng->txt("update"),
            ""
        );


        return $toolbar->getHTML();
    }

    /**
     * Apply filter
     * @param
     * @return
     */
    protected function applyFilter()
    {
        global $DIC;

        $ctrl = $DIC->ctrl();
        $from = new ilDateTimeInputGUI("", "from");
        $from->checkInput();
        $f = (is_null($from->getDate()))
            ? ""
            : $from->getDate()->get(IL_CAL_DATETIME);
        $to = new ilDateTimeInputGUI("", "to");
        $to->checkInput();
        $t = (is_null($to->getDate()))
            ? ""
            : $to->getDate()->get(IL_CAL_DATETIME);


        ilSession::set("skmg_pf_from", $f);
        ilSession::set("skmg_pf_to", $t);

        $ctrl->setParameterByClass("ilcontskillpresentationgui", "profile_id", (int) $_POST["profile_id"]);
        //$ctrl->setParameterByClass("ilcontskillpresentationgui", "from", $f);
        //$ctrl->setParameterByClass("ilcontskillpresentationgui", "to", $t);

        $ctrl->redirectByClass("ilcontskillpresentationgui", "");
    }

}
?>
