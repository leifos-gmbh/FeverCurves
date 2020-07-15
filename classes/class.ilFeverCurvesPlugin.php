<?php

/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");

/**
 * Example user interface plugin
 *
 * @author Alexander Killing <killing@leifos.de>
 */
class ilFeverCurvesPlugin extends ilUserInterfaceHookPlugin
{
    function getPluginName()
    {
        return "FeverCurves";
    }

    function afterUninstall()
    {
        global $ilDB;

        if ($ilDB->tableExists("skl_profile_fever")) {
            $ilDB->dropTable("skl_profile_fever");
        }
    }
}

?>
