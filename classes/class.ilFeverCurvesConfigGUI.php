<?php
/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Component\MessageBox\MessageBox;

/**
 * Class ilFeverCurvesConfigGUI
 * @author Thomas Famula <famula@leifos.de>
 */
class ilFeverCurvesConfigGUI extends ilPluginConfigGUI
{
    protected $ctrl;
    protected $lng;
    protected $tpl;
    protected $ui_factory;
    protected $ui_renderer;
    protected $request;


    /**
     * @param $cmd
     *
     * Handles all commmands, default is "configure"
     */
    function performCommand($cmd)
    {
        switch ($cmd)
        {
            default:
                $this->$cmd();
                break;
        }
    }

    /**
     * @param Form|null $form
     *
     * Show settings screen
     */
    function configure(Form $form = null)
    {
        global $DIC;

        $this->tpl = $DIC["tpl"];
        $this->ui_renderer = $DIC->ui()->renderer();

        $info = $this->getInfoText();

        if(!$form instanceof Form)
        {
            $form = $this->initConfigurationForm();
        }
        $this->tpl->setContent($this->ui_renderer->render([$info, $form]));
    }

    /**
     * Get info text
     *
     * @return MessageBox
     */
    function getInfoText() : MessageBox
    {
        global $DIC;

        $this->ui_factory = $DIC->ui()->factory();

        $info_txt = $this->ui_factory->messageBox()->info($this->getPluginObject()->txt("skl_levels_info"));

        return $info_txt;
    }

    /**
     * Init configuration form
     *
     * @return Form
     */
    function initConfigurationForm() : Form
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC["tpl"];
        $this->lng = $DIC->language();
        $this->ui_factory = $DIC->ui()->factory();
        $this->request = $DIC->http()->request();

        $this->getPluginObject()->includeClass("class.ilFeverCurvesSkillProfileRepository.php");


        // get all competence profiles
        $profiles = array();
        $profile_ids = array();
        foreach (ilSkillProfile::getProfiles() as $profile) {
            $profiles[$profile["id"]] = $profile["title"];
            $profile_ids[$profile["id"]] = $profile["id"];
        }

        $activated_profile_ids = $profile_ids;
        $deactivated_profile_ids = ilFeverCurvesSkillProfileRepository::getDeactivatedProfileIds();

        // get deactivated competence profiles which should be unchecked in the form
        if (!empty($deactivated_profile_ids)) {
            foreach ($deactivated_profile_ids as $id) {
                if (in_array($id, $activated_profile_ids)) {
                    unset($activated_profile_ids[$id]);
                }
            }
        }

        // create multiselect input and set all activated competence profiles as checked
        $multi_select_profiles = $this->ui_factory->input()->field()->multiselect(
            $this->getPluginObject()->txt("skl_profiles"),
            $profiles,
            $this->getPluginObject()->txt("skl_profiles_fever_selection_info")
        )
        ->withValue($activated_profile_ids);

        // radio input for colour scheme selection
        $this->getPluginObject()->includeClass("class.ilFeverCurvesSettingsRepository.php");
        $fever_settings = new ilFeverCurvesSettingsRepository();
        $radio_colour = $this->ui_factory->input()->field()->radio(
            $this->getPluginObject()->txt("colour_scheme"),
            $this->getPluginObject()->txt("colour_scheme_info")
        )
            ->withOption("0", $this->getPluginObject()->txt("colour_scheme_ilias"))
            ->withOption("1", $this->getPluginObject()->txt("colour_scheme_random"))
            ->withValue((int) $fever_settings->getActiveRandomColours());

        // form sections
        $section_profiles = $this->ui_factory->input()->field()->section(
            ['checked_profiles' => $multi_select_profiles],
            $this->getPluginObject()->txt("skl_profiles_fever_selection")
        );
        $section_presentation = $this->ui_factory->input()->field()->section(
            ['colour_scheme' => $radio_colour],
            $this->getPluginObject()->txt("colour_scheme_presentation")
        );

        // form and form action handling
        $this->ctrl->setParameterByClass(
            'ilfevercurvesconfiggui',
            'fever_curves',
            'fever_curves_config'
        );
        $form_action = $this->ctrl->getFormAction($this);
        $form = $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            ["section_profiles" => $section_profiles, "section_presentation" => $section_presentation]
        );

        if ($this->request->getMethod() == "POST"
            && $this->request->getQueryParams()['fever_curves'] == "fever_curves_config") {
            $form = $form->withRequest($this->request);
            $result = $form->getData();

            // save (un)checked status for all competence profiles in database
            foreach ($profile_ids as $id) {
                $profile = new ilFeverCurvesSkillProfileRepository($id);
                if (!empty($result["section_profiles"]["checked_profiles"])
                    && in_array($id, $result["section_profiles"]["checked_profiles"])
                ) {
                    $profile->updateActivation(1);
                } else {
                    $profile->updateActivation(0);
                }
            }

            // save colour scheme
            $fever_settings->setActiveRandomColours($result["section_presentation"]["colour_scheme"]);

            ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
            $this->ctrl->redirect($this);
        }

        return $form;
    }
}