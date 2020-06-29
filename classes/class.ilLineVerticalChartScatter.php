<?php
/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Generator for scatter charts
 * @author Thomas Famula <famula@leifos.de>
 */
class ilLineVerticalChartScatter extends ilLineVerticalChart
{
    /**
     * @var int|null
     */
    protected $width;

    /**
     * @var int|null
     */
    protected $height;

    /**
     * @var array|null
     */
    protected $x_labels;

    /**
     * @var array|null
     */
    protected $y_labels;

    /**
     * @var int
     */
    protected $x_step_size;

    /**
     * @var int
     */
    protected $y_step_size;

    /**
     * @var int
     */
    protected $x_min;

    /**
     * @var int
     */
    protected $y_min;

    /**
     * @var int
     */
    protected $x_max;

    /**
     * @var int
     */
    protected $y_max;

    /**
     * @var int
     */
    protected $x_padding;

    /**
     * @var int
     */
    protected $y_padding;


    public function __construct($a_id, $plugin)
    {
        parent::__construct($a_id, $plugin);

        $this->setWidth(1200);
        $this->setHeight(600);
        $this->setXAxisStepSize(1);
        $this->setXAxisMin(0);
        $this->setXAxisPadding(10);
        $this->setYAxisStepSize(1);
        $this->setYAxisMin(0);
        $this->setYAxisPadding(10);
    }

    public function getWidth() : int
    {
        return $this->width;
    }

    public function setWidth(int $a_width)
    {
        $this->width = $a_width;
    }

    public function getHeight() : int
    {
        return $this->height;
    }

    public function setHeight(int $a_height)
    {
        $this->height = $a_height;
    }

    /**
     * @return array
     */
    public function getXAxisLabels() : ?array
    {
        return $this->x_labels;
    }

    /**
     * @param array $a_labels
     */
    public function setXAxisLabels(array $a_labels)
    {
        $this->x_labels = $a_labels;
    }

    /**
     * @return array
     */
    public function getYAxisLabels() : ?array
    {
        return $this->y_labels;
    }

    /**
     * @param array $a_labels
     */
    public function setYAxisLabels(array $a_labels)
    {
        $this->y_labels = $a_labels;
    }

    /**
     * @return int
     */
    public function getXAxisStepSize() : int
    {
        return $this->x_step_size;
    }

    /**
     * @param int $a_step_size
     */
    public function setXAxisStepSize(int $a_step_size)
    {
        $this->x_step_size = $a_step_size;
    }

    /**
     * @return int
     */
    public function getYAxisStepSize() : int
    {
        return $this->y_step_size;
    }

    /**
     * @param int $a_step_size
     */
    public function setYAxisStepSize(int $a_step_size)
    {
        $this->y_step_size = $a_step_size;
    }

    /**
     * @return int
     */
    public function getXAxisMin() : int
    {
        return $this->x_min;
    }

    /**
     * @param int $a_min
     */
    public function setXAxisMin(int $a_min)
    {
        $this->x_min = $a_min;
    }

    /**
     * @return int
     */
    public function getYAxisMin() : int
    {
        return $this->y_min;
    }

    /**
     * @param int $a_min
     */
    public function setYAxisMin(int $a_min)
    {
        $this->y_min = $a_min;
    }

    /**
     * @return int
     */
    public function getXAxisMax() : int
    {
        return $this->x_max;
    }

    /**
     * @param int $a_max
     */
    public function setXAxisMax(int $a_max)
    {
        $this->x_max = $a_max;
    }

    /**
     * @return int
     */
    public function getYAxisMax() : int
    {
        return $this->y_max;
    }

    /**
     * @param int $a_max
     */
    public function setYAxisMax(int $a_max)
    {
        $this->y_max = $a_max;
    }

    /**
     * @return int
     */
    public function getXAxisPadding() : int
    {
        return $this->x_padding;
    }

    /**
     * @param int $a_padding
     */
    public function setXAxisPadding(int $a_padding)
    {
        $this->x_padding = $a_padding;
    }

    /**
     * @return int
     */
    public function getYAxisPadding() : int
    {
        return $this->y_padding;
    }

    /**
     * @param int $a_padding
     */
    public function setYAxisPadding(int $a_padding)
    {
        $this->y_padding = $a_padding;
    }


    public function parseOptions(array &$a_options)
    {
        $preferences = new stdClass();

        // responsiveness
        $preferences->responsive = true;
        $preferences->maintainAspectRatio = false;

        // tooltips
        $preferences->tooltips = new stdClass();
        $preferences->tooltips->enabled = true;

        // legend
        $preferences->legend = new stdClass();
        $preferences->legend->position = "right";

        // x-axis
        $preferences->xAxis = new stdClass();
        $preferences->xAxis->type = "linear";
        $preferences->xAxis->beginAtZero = true;
        $preferences->xAxis->stepSize = $this->getXAxisStepSize();
        $preferences->xAxis->min = $this->getXAxisMin();
        $preferences->xAxis->max = $this->getXAxisMax();
        $preferences->xAxis->padding = $this->getXAxisPadding();

        // y-axis
        $preferences->yAxis = new stdClass();
        $preferences->yAxis->type = "linear";
        $preferences->yAxis->reverse = true;
        $preferences->yAxis->beginAtZero = true;
        $preferences->yAxis->min = $this->getYAxisMin();
        $preferences->yAxis->max = $this->getYAxisMax();
        $preferences->yAxis->padding = $this->getYAxisPadding();

        $a_options = $preferences;
    }
}
