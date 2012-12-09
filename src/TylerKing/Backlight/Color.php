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

use Imagick;
use TylerKing\Backlight\Manipulator;

class Color
{
    private $red,
            $green,
            $blue,
            $hue,
            $saturation,
            $value,
            $image;

    public function setImage($image)
    {
        $manipulator   = new Manipulator;
        $this->image   = $manipulator->setImage($image)->getImage();

        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getMap()
    {
        $size     = $this->image->getImageGeometry();
        $width    = $size['width'];
        $height   = $size['height'];

        $map = [];
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $colors = $this->image->getImagePixelColor($x, $y)->getColor();

                $this->red      = $colors['r'];
                $this->green    = $colors['g'];
                $this->blue     = $colors['b'];

                $map[] = [$x, $y, $this->rgb2hex()];
            }
        }

        return $map;
    }

    public function getMean()
    {
        $size     = $this->image->getImageGeometry();
        $width    = $size['width'];
        $height   = $size['height'];

        $red_total    = 0;
        $green_total  = 0;
        $blue_total   = 0;
        $total        = 0.0;

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $colors = $this->image->getImagePixelColor($x, $y)->getColor();

                $saturation = (max($colors['r'], max($colors['g'], $colors['b']))-min($colors['r'], min($colors['g'], $colors['b'])));
                $relevance  = 0.1+0.9*($colors['a'])*$saturation;

                $red_total    += $colors['r']*$relevance;
                $green_total  += $colors['g']*$relevance;
                $blue_total   += $colors['b']*$relevance;
                $total        += $relevance;
            }
        }

        $this->red    = round($red_total/$total);
        $this->green  = round($green_total/$total);
        $this->blue   = round($blue_total/$total);

        $background_rgb = [$this->red, $this->green, $this->blue];
        $background_hex = $this->rgb2hex();

        $this->rgb2hsv();
        if ($this->saturation > 0.15) {
            $this->saturation = 65;
        }
        $this->value = 90;

        $this->hsv2rgb();
        $glow_rgb   = [$this->red, $this->green, $this->blue];
        $glow_hex   = $this->rgb2hex();

        return (object) [
                            'mean'  => (object) [
                                'rgb'   => $background_rgb,
                                'hex'   => $background_hex
                            ],
                            'glow'  => (object) [
                                'rgb'   => $glow_rgb,
                                'hex'   => $glow_hex
                            ]
                        ];
    }

    public function setRed($red)
    {
        $this->red = $red;

        return $this;
    }

    public function getRed()
    {
        return $this->red;
    }

    public function setGreen($green)
    {
        $this->green = $green;

        return $this;
    }

    public function getGreen()
    {
        return $this->green;
    }

    public function setBlue($blue)
    {
        $this->blue = $blue;

        return $this;
    }

    public function getBlue()
    {
        return $this->blue;
    }

    public function setHue($hue)
    {
        $this->hue = $hue;

        return $this;
    }

    public function getHue()
    {
        return $this->hue;
    }

    public function setSaturation($saturation)
    {
        $this->saturation = $saturation;

        return $this;
    }

    public function getSaturation()
    {
        return $this->saturation;
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function rgb2hsv()
    {
        $min    = min($this->red, $this->green, $this->blue);
        $max    = max($this->red, $this->green, $this->blue);
        $delta  = $max-$min;

        $h = $s = $v = $max;

        $v = floor($max/255*100);
        if ($max != 0) {
            $s = floor($delta/$max*100);
        } else {
            return [0, 0, 0];
        }

        if ($this->red == $max) {
            $h = ($this->green-$this->blue)/$delta;
        } elseif ($this->green == $max) {
            $h = 2+($this->blue-$this->red)/$delta;
        } else {
            $h = 4+($this->red-$this->green)/$delta;
        }

        $h = floor($h*60);
        if ($h < 0) {
            $h += 360;
        }

        $this->hue         = $h;
        $this->saturation  = $s;
        $this->value       = $v;

        return $this;
    }

    public function hsv2rgb()
    {
        $this->hue %= 360;

        if ($this->value == 0) {
            $this->red    = 0;
            $this->green  = 0;
            $this->red    = 0;

            return $this;
        }

        $this->saturation /= 100;
        $this->value      /= 100;
        $this->hue        /= 60;

        $i = floor($this->hue);
        $f = $this->hue-$i;
        $p = $this->value*(1-$this->saturation);
        $q = $this->value*(1-($this->saturation*$f));
        $t = $this->value*(1-($this->saturation*(1-$f)));

        switch ($i) {
            case 0:
                $red = $this->value;
                $grn = $t;
                $blu = $p;
                break;
            case 1:
                $red = $q;
                $grn = $this->value;
                $blu = $p;
                break;
            case 2:
                $red = $p;
                $grn = $this->value;
                $blu = $t;
                break;
            case 3:
                $red = $p;
                $grn = $q;
                $blu = $this->value;
                break;
            case 4:
                $red = $t;
                $grn = $p;
                $blu = $this->value;
                break;
            case 5:
                $red = $this->value;
                $grn = $p;
                $blu = $q;
                break;
        }

        $this->red   = floor($red*255);
        $this->green = floor($grn*255);
        $this->blue  = floor($blu*255);

        return $this;
    }

    public function rgb2hex()
    {
        return '#'.str_pad(dechex($this->red), 2, '0', STR_PAD_LEFT).str_pad(dechex($this->green), 2, '0', STR_PAD_LEFT) . str_pad(dechex($this->blue), 2, '0', STR_PAD_LEFT);
    }

    public function hsv2hex()
    {
        $this->hsv2rgb();

        return $this->rgb2hex();
    }
}
