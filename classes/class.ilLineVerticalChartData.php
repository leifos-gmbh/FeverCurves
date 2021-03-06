<?php
/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/FeverCurves/classes/class.ilFeverCurvesSettingsRepository.php");

/**
 * Abstract chart data series base class
 *
 * @author Thomas Famula <famula@leifos.de>
 */
abstract class ilLineVerticalChartData
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $color;

    /**
     * Get series type
     *
     * @return string
     */
    abstract protected function getTypeString();

    /**
     * Set data
     *
     * @param float $a_x
     * @param float $a_y
     */
    public function addPoint(float $a_x, float $a_y)
    {
        $this->data[] = array($a_x, $a_y);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getPoints()
    {
        return $this->data;
    }

    /**
     * Set label
     *
     * @param string $a_label
     */
    public function setLabel(string $a_label)
    {
        $this->label = $a_label;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    public function setColor(string $a_color)
    {
        $this->color = $a_color;
    }

    public function getColor()
    {
        return $this->color;
    }


    /**
     * Convert data to chart.js config
     *
     * @param array $a_data
     * @return object
     */
    public function parseData(array &$a_data)
    {
        $series = new stdClass();
        $series->label = $this->getLabel();
        $series->data = array();
        if (is_array($this->getPoints())) {
            foreach ($this->getPoints() as $point) {
                $series->data[] = ["x" => $point[0], "y" => $point[1]];
            }
        }
        $fever_settings = new ilFeverCurvesSettingsRepository();
        if (!(bool) $fever_settings->getActiveRandomColours()) {
            $series->borderColor = $this->getColor();
            $series->backgroundColor = $this->getColor();
            $series->pointBorderColor = $this->getColor();
            $series->pointBackgroundColor = $this->getColor();
        }

        $a_data[] = $series;
    }

}
