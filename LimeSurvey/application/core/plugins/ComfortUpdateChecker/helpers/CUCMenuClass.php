<?php

/**
 * Extending the basic menu class with an icon in front of the label
 */

namespace ComfortUpdateChecker\helpers;

class CUCMenuClass extends \LimeSurvey\Menu\Menu
{
    public function getLabel()
    {
        return "<i class='" . $this->iconClass . "'></i>&nbsp;" . $this->label;
    }
}
