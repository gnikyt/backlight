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

class Manipulator
{
    private $image,
            $path,
            $width,
            $type,
            $height;

    public function setImage($image)
    {
        $this->path   = $image;
        $this->image  = new Imagick($image);

        $this->getType();
        $this->fixOrientation();
        $this->getSize();

        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getType()
    {
        if (isset($this->type)) {
            return $this->type;
        }

        $type       = getimagesize($this->path);
        $this->type = $type['mime'];

        return $this->type;
    }

    public function fixOrientation() {
        if (!in_array($this->type, ['image/jpeg', 'image/jpg', 'image/tiff', 'image/tiff'])) {
            return false;
        }

        $exif = exif_read_data($this->path);

        if (isset($exif['Orientation'])) {
            $orientation = $exif['Orientation'];
        } elseif (isset($exif['IFD0']['Orientation'])) {
            $orientation = $exif['IFD0']['Orientation'];
        } else {
            return false;
        }

        switch ($orientation) {
            case 3: // rotate 180 degrees
                $this->image->rotateImage('#FFF', 180);
                break;
            case 6: // rotate 90 degrees CW
                $this->image->rotateImage('#FFF', 90);
                break;
            case 8: // rotate 90 degrees CCW
                $this->image->rotateImage('#FFF', -90);
                break;
        }
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getSize()
    {
        $size = $this->image->getImageGeometry();

        $this->width  = $size['width'];
        $this->height = $size['height'];

        return [$this->width, $this->height];
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function resize($width, $height, $filter = Imagick::FILTER_LANCZO)
    {
        $this->image->resizeImage($width, $height, Imagick::FILTER_LANCZO, 1);
    }

    public function resizeToHeight($height)
    {
        $ratio  = $height/$this->height;
        $width  = $this->width*$ratio;

        $this->resize($width, $height);

        return $this;
    }

    public function resizeToWidth($width)
    {
        $ratio  = $width/$this->width;
        $height = $this->height*$ratio;

        $this->resize($width, $height);

        return $this;
    }

    public function crop($width, $height, $x_pos, $y_pos)
    {
        if ($this->width/$this->height != $width/$height) {
            $width_tmp  = $width;
            $height_tmp = $height;

            if ($this->width/$this->height > $width/$height) {
                $width  = $this->width*$height/$this->height;
                $x_pos  = -($width-$width_tmp)/2;
                $y_pos  = 0;
            } else {
                $height = $this->height*$width/$this->width;
                $y_pos  = -($height - $height_tmp)/2;
                $x_pos  = 0;
            }
        }

        $this->image->cropImage($width, $height, $x_pos, $y_pos);

        return $this;
    }

    public function rotate($degree, $fill = '#FFF')
    {
        $this->image->rotateImage($degree, $fill);

        return $this;
    }

    public function save($path = null)
    {
        $this->image->writeImage($path ?: $this->path);
        $this->image->clear();
        $this->image->destroy();
    }
}
