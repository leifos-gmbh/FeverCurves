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
    protected $involved_courses = array();

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

            /*
            $this->getPluginObject()->includeClass("class.ilOptesUI.php");
            $o = new ilOptesUI();

            if ($o->getTriggerSkill() != $a_par["top_skill_id"])
            {
                return array("mode" => ilUIHookPluginGUI::KEEP, "html" => "");
            }

            $this->getPluginObject()->includeClass("class.ilOptesChart.php");
            $this->getPluginObject()->includeClass("class.ilChartBubble.php");
            $this->getPluginObject()->includeClass("class.ilChartDataBubble.php");

            $tpl = $this->getPluginObject()->getTemplate("tpl.skill_addon.html");

            include_once("./Services/UIComponent/Panel/classes/class.ilPanelGUI.php");
            $p = ilPanelGUI::getInstance();

            // main
            $tpl->setCurrentBlock("chart");
            $tpl->setVariable("TITLE", $this->getPluginObject()->txt("overview_all_courses"));
            $tpl->setVariable("CHART", $this->getChartHTML(0, $a_par["user_id"]));
            $tpl->parseCurrentBlock();
            $ac = ilUtil::sortArray($this->involved_courses, "crs_title", "asc");
            foreach ($ac as $tst_ref_id => $crs)
            {
                $tpl->setCurrentBlock("chart");
                $tpl->setVariable("TITLE", $lng->txt("obj_crs").": ".$crs["crs_title"]);
                $tpl->setVariable("CHART", $this->getChartHTML($crs["crs_ref_id"], $a_par["user_id"]));
                $tpl->parseCurrentBlock();
            }

            $p->setBody($a_par["personal_skills_gui"]->renderSkillHTML($a_par["top_skill_id"], $a_par["user_id"],
                $a_par["edit"], $a_par["tref_id"]));

            include_once("./Services/Accordion/classes/class.ilAccordionGUI.php");
            $acc = new ilAccordionGUI();
            $acc->setId("optes_skill_acc");
            $acc->addItem($this->getPluginObject()->txt("detail_skill_pres"),
                $p->getHTML());
            */

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

        $skills = array(); // Kompetenzen mit jeweiliger Zielausprägung
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
        $all_chart_html = "";



        $comp_labels = array();
        $level_labels = array();
        $all_level_entries = array();
        $cnt_entries = 0;

        $numbers = array();
        $types = array();

        foreach ($skills as $k => $l) {

            $bs = new ilBasicSkill($l["base_skill_id"]);
            $comp_labels[] = ilBasicSkill::_lookupTitle($l["base_skill_id"], $l["tref_id"]);
            $levels = $bs->getLevelData(); //mögliche Kompetenzeinträge


            // get all object triggered entries
            $level_entries = array(); //alle Kompetenzeinträge aus dem Kurs
            foreach ($bs->getAllHistoricLevelEntriesOfUser($l["tref"], $ilUser->getId(),
                ilBasicSkill::EVAL_BY_ALL) as $entry) {
                if (count($a_par["personal_skills_gui"]->getTriggerObjectsFilter()) && !in_array($entry['trigger_obj_id'],
                        $a_par["personal_skills_gui"]->getTriggerObjectsFilter())) {
                    continue;
                }

                if ($a_par["personal_skills_gui"]->getFilter()->isInRange($levels, $entry)) {
                    $level_entries[] = $entry;
                }
            }
            $cnt_entries = sizeof($level_entries);
            $all_level_entries[] = $level_entries;


            $cnt = 0;
            foreach ($levels as $lv) {
                $cnt++;
                if ($l["level_id"] == $lv["id"]) {
                    $skills[$k]["target_cnt"] = $cnt;
                }

                if (!in_array($lv["title"], $level_labels)) {
                    $level_labels[] = $lv["title"];
                }
            }

            $nums = array();
            $type = array();
            foreach ($level_entries as $entry) {
                $num_data = $bs->getLevelData($entry["level_id"]);
                $num = (float) $num_data["nr"];
                $num = $num + (float) $entry["next_level_fulfilment"];
                $nums[] = $num;

                if ($entry["self_eval"] == 1) {
                    $type[] = ilSkillEval::TYPE_SELF_EVAL;
                }
                else if ($entry["trigger_obj_type"] == "tst") {
                    $type[] = ilSkillEval::TYPE_MEASUREMENT;
                }
                else {
                    $type[] = ilSkillEval::TYPE_APPRAISAL;
                }

            }
            $numbers[] = $nums;
            $types[] = $type;
        }


        //var_dump($all_level_entries); exit;

        for($ct = 0; $ct < $cnt_entries; $ct++) {
            $merged[] = array_column($numbers, $ct);
        }


        $scatter_chart = new ilLineVerticalChartScatter("fever_curves", $this->getPluginObject());
        $scatter_chart->setYAxisMax(sizeof($level_labels) - 1); // eleganteren Weg finden?
        $scatter_chart->setXAxisMax(sizeof($levels) - 1); //// eleganteren Weg finden?
        $scatter_chart->setYAxisLabels($comp_labels);
        $scatter_chart->setXAxisLabels($level_labels);

        // target level
        $scatter_data1 = new ilLineVerticalChartDataScatter();
        $scatter_data1->setLabel($lng->txt("skmg_target_level"));
        //$scatter_data1->setColor("green"); // change to hex code




        // fill in data
        $cnt = 0;
        //var_dump($skills); exit;
        foreach ($skills as $pl) {
            $scatter_data1->addPoint(((int) $pl["target_cnt"] - 1), $cnt); // addPoint prüfen ob Wert überhaupt vorhanden, ansonsten überspringen
            $cnt++;
        }



        if (is_array($merged)) {
            foreach ($merged as $i => $line) {
                $scatter_data2 = new ilLineVerticalChartDataScatter();
                $scatter_data2->setLabel($lng->txt("skmg_eval_type_" . $types[0][$i]));
                //$scatter_data2->setColor("red"); // change to hex code

                $c = 0;
                foreach ($line as $point) {
                    $scatter_data2->addPoint((float) $point - 1, $c); // addPoint prüfen ob Wert überhaupt vorhanden, ansonsten überspringen
                    $c++;
                }

                $scatter_chart->addData($scatter_data2);

            }
        }
        //exit;



        // add data to chart
        if ($a_par["personal_skills_gui"]->getProfileId() > 0) {
            $scatter_chart->addData($scatter_data1);
        }



        $scatter_chart_html = $scatter_chart->getHTML();

        $all_chart_html .= $scatter_chart_html;


        $pan = ilPanelGUI::getInstance();
        $pan->setPanelStyle(ilPanelGUI::PANEL_STYLE_PRIMARY);
        $pan->setBody($all_chart_html);
        $all_chart_html = $pan->getHTML();

        return $all_chart_html;
    }


    /**
     * Get chart HTML
     *
     * @param
     * @return
     */
    /*
    function getChartHTML($a_crs_ref_id, $a_user_id)
    {
        $chart = ilOptesChart::getInstanceByType(ilOptesChart::TYPE_BUBBLE, "optes_chart_".$a_crs_ref_id);
        $chart->setPluginObject($this->getPluginObject());
        //$chart = new ilChartBubble("optes_chart_".$a_crs_ref_id, 850, 250);
        $pl = $this->getPluginObject();
        $pl->includeClass("class.ilOptesUI.php");
        $o = new ilOptesUI();
        $rows = $o->getRows();

        // remove rows without data
        foreach ($o->getCols() as $c)
        {
            reset($rows);
            foreach ($rows as $k => $r)
            {
                $val = $o->getCompetenceValueForMatrix($r["id"], $c["id"], $a_user_id, $a_crs_ref_id);
                if ($val !== null)
                {
                    $rows[$k]["got_data"] = true;
                }
            }
        }
        $rows = array_filter($rows, function($r) {
            return (isset($r["got_data"]));
        });
        if (count($rows) == 0)
        {
            return '<div class="alert alert-info" role="info">'.
                '<h5 class="ilAccHeadingHidden"><a id="il_message_focus" name="il_message_focus">Fehlermeldung</a></h5>'.
                $this->getPluginObject()->txt("no_data_yet").'</div>';
        }

        // set height
        $height = (40 * count($rows)) + 50;
        $chart->setSize(850, $height);

        // fill char
        $yticks = $xticks = array();
        $cnt = 0;
        foreach ($rows as $r)
        {
            $yticks[count($rows) - ($cnt++)] = $r["title"];
        }
        foreach ($o->getCols() as $c)
        {
            $cd = new ilChartDataBubble();
            $xticks[++$i] = $c["title"];
            reset($rows);
            $cnt = 0;
            foreach ($rows as $r)
            {
                $val = $o->getCompetenceValueForMatrix($r["id"], $c["id"], $a_user_id, $a_crs_ref_id);
                $cd->addPoint($i, count($rows) - ($cnt++), round($val, 2));
            }
            $chart->addData($cd);
        }
        $chart->setTicks($xticks, $yticks, true);
        $chart->setMinMax(0.5, 0.5 + count($o->getCols()), 0, 1 + count($rows));
        $chart_html = $chart->getHTML();
        if ($a_crs_ref_id == 0)
        {
            $this->involved_courses = array();
            $ics = $o->getInvolvedCourses();
            foreach ($ics as $k => $v)
            {
                $this->involved_courses[$v["crs_ref_id"]] = $v;
            }
        }
        return $chart_html;
    }*/

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
