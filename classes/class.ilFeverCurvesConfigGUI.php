<?php
/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");

use ILIAS\UI\Component\Input\Container\Form\Form;

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

        if(!$form instanceof Form)
        {
            $form = $this->initConfigurationForm();
        }
        $this->tpl->setContent($this->ui_renderer->render($form));
    }

    /**
     * @return
     *
     * Init configuration form
     */
    function initConfigurationForm()
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC["tpl"];
        $this->lng = $DIC->language();
        $this->ui_factory = $DIC->ui()->factory();
        $this->request = $DIC->http()->request();

        $this->getPluginObject()->includeClass("class.ilFeverCurvesSkillProfile.php");


        // get all competence profiles
        $profiles = array();
        $profile_ids = array();
        foreach (ilSkillProfile::getProfiles() as $profile) {
            $profiles[$profile["id"]] = $profile["title"];
            $profile_ids[$profile["id"]] = $profile["id"];
        }

        $activated_profile_ids = $profile_ids;
        $deactivated_profile_ids = ilFeverCurvesSkillProfile::getDeactivatedProfileIds();

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
            $this->getPluginObject()->txt("skl_profiles_fever_selection")
        )
        ->withValue($activated_profile_ids);

        // form and form action handling
        $this->ctrl->setParameterByClass(
            'ilfevercurvesconfiggui',
            'profiles',
            'profiles_selected'
        );
        $form_action = $this->ctrl->getFormAction($this);
        $form = $this->ui_factory->input()->container()->form()->standard($form_action, ['checked_profiles' => $multi_select_profiles]);

        if ($this->request->getMethod() == "POST"
            && $this->request->getQueryParams()['profiles'] == "profiles_selected") {
            $form = $form->withRequest($this->request);
            $result = $form->getData();

            // save (un)checked status for all competence profiles in database
            foreach ($profile_ids as $id) {
                $profile = new ilFeverCurvesSkillProfile($id);
                if (!empty($result["checked_profiles"]) && in_array($id, $result["checked_profiles"])) {
                    $profile->updateProfile(1);
                } else {
                    $profile->updateProfile(0);
                }
            }
            ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
        }

        return $form;
    }
}