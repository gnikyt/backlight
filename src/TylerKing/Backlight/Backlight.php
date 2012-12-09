<?php

/*
 * This file is part of Backlight
 *
 * Tyler King <tyler.king@newfie.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TylerKing\Backlight;

use TylerKing\Backlight\Color;

class Backlight
{
    private $image,
            $result;

    public function __construct($image)
    {
        $this->image = $image;

        return $this;
    }

    public function getBackground()
    {
        if (isset($this->result)) {
            return $this->result->mean;
        }

        $color         = new Color;
        $this->result  = $color->setImage($this->image)->getMean();

        return $this->result->mean;
    }

    public function getGlow()
    {
        if (isset($this->result)) {
            return $this->result->glow;
        }

        $color         = new Color;
        $this->result  = $color->setImage($this->image)->getMean();

        return $this->result->glow;
    }

    public function toHTML()
    {
        $color  = new Color;
        $map    = $color->setImage($this->image)->getMap();

        $html = ['<style>.bl{position:absolute;width:1px;height:1px}</style>'];
        foreach ($map as $entry) {
            list($x, $y, $color) = $entry;

            $html[] = "<div class='bl' style='top:{$y}px;left:{$x}px;background-color:{$color}'></div>";
        }

        return implode('', $html);
    }
}
