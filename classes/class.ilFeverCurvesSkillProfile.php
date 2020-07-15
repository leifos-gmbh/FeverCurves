<?php
/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Fever curves skill profiles class
 *
 * @author Thomas Famula <famula@leifos.de>
 */
class ilFeverCurvesSkillProfile
{
    /**
     * @var int $id
     */
    protected $id;

    /**
     * ilFeverCurvesSkillProfiles constructor.
     */
    public function __construct(int $a_id)
    {
        $this->id = $a_id;
    }

    protected function getId()
    {
        return $this->id;
    }

    /**
     * Get profiles which are (de)activated for fever curves
     *
     * @return array
     */
    public static function getProfiles()
    {
        global $DIC;

        $ilDB = $DIC->database();

        $set = $ilDB->query(
            "SELECT * FROM skl_profile_fever"
        );
        $profiles = array();
        while ($rec = $ilDB->fetchAssoc($set)) {
            $profiles[$rec["profile_id"]] = $rec;
        }

        return $profiles;
    }

    /**
     * Get ids from profiles which are deactivated for fever curves
     *
     * @param
     * @return
     */
    public static function getDeactivatedProfileIds()
    {
        global $DIC;

        $ilDB = $DIC->database();

        $set = $ilDB->query(
            "SELECT * FROM skl_profile_fever " .
            " WHERE active = 0"
        );
        $profile_ids = array();
        while ($rec = $ilDB->fetchAssoc($set)) {
            $profile_ids[] = $rec["profile_id"];
        }

        return $profile_ids;
    }

    public function updateProfile(int $active)
    {
        global $DIC;

        $ilDB = $DIC->database();

        $set = $ilDB->query(
            "SELECT * FROM skl_profile_fever " .
            " WHERE profile_id = " . $this->getId()
        );

        if ($rec = $ilDB->fetchAssoc($set)) {
            $ilDB->manipulate(
                "UPDATE skl_profile_fever SET " .
                " active = " . $ilDB->quote($active, "integer") .
                " WHERE profile_id = " . $ilDB->quote($this->getId(), "integer")
            );
        }
        else {
            $ilDB->manipulate("INSERT INTO skl_profile_fever " .
                "(profile_id, active) VALUES (" .
                $ilDB->quote($this->getId(), "integer") . "," .
                $ilDB->quote($active, "integer") .
                ")");
        }
    }
}