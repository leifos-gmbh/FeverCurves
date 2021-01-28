<?php

/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Fever curves settings
 *
 * @author Thomas Famula <famula@leifos.de>
 */
class ilFeverCurvesSettingsRepository extends ilSetting
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct("skmg");
    }

    /**
     * Activate random colours for fever curves
     *
     * @param $a_active
     */
    public function setActiveRandomColours($a_active)
    {
        $this->set("fever_random_colours", $a_active);
    }

    /**
     * Are random colours for fever curves activated
     *
     * @return string
     */
    public function getActiveRandomColours()
    {
        return $this->get("fever_random_colours");
    }
}
