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
}

?>
