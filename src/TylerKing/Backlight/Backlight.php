<?php

/*
 * Backlight.php
 *
 * Backlight is a pointless script inspired by the Ubuntu Unity Launcher. It will
 * take an image/icon, and find it's mean background color as well as it's glow
 * color. It will return an object with values in both RGB and HEX format for use.
 *
 * Tyler King <tyler.king@newfie.co>
 *
 * idea inspired by: http://bazaar.launchpad.net/~unity-team/nux/trunk/view/head:/NuxCore/Color.cpp
 * rgbToHsv based on function from: http://stackoverflow.com/a/2348659
 * hsvToRgb based on function from: http://www.actionscript.org/forums/showthread.php3?t=15155
 */

namespace TylerKing\Backlight;

class Backlight
{
    private $image,
            $type,
            $path,
            $info,
            $width,
            $height;

    public function __construct( )
    {
        return $this;
    }

    private function error( $message )
    {
        throw new Exception( $message );
    }

    public function setPath( $path )
    {
        if ( !file_exists( $path ) || !getimagesize( $path ) ) {
            return $this->error( 'The image must have a valid path and be an image.' );
        }

        $this->path = $path;
        $this->setInfo( );

        return $this;
    }

    public function getPath( )
    {
        return $this->path;
    }

    public function setType( $type )
    {
        $error = false;
        if ( is_int( $type ) ) {
            if ( !in_array( $type, array( IMAGETYPE_JPEG, IMAGETYPE_PNG ) ) ) {
                $error = true;
            }
        } else {
            $type = strtolower( $type );
            switch ($type) {
                case 'jpeg' :
                case 'jpg' :
                case 'image/jpeg' :
                case 'image/jpg' :
                    $type = IMAGETYPE_JPEG;
                    break;
                case 'png' :
                case 'image/png' :
                    $type = IMAGETYPE_PNG;
                    break;
                default :
                    $error = true;
                    break;
            }
        }

        if ($error) {
            return $this->error( 'Image type not allowed. JPEG and PNG accepted.' );
        }

        $this->type = $type;

        return $this;
    }

    public function getType( )
    {
        return $this->type;
    }

    public function setImage( $image )
    {
        $this->image = $image;

        return $this;
    }

    public function getImage( )
    {
        return $image;
    }

    private function setInfo( )
    {
        $this->info     = getimagesize( $this->path );
        $this->width    = $this->info[ 0 ];
        $this->height   = $this->info[ 1 ];

        return $this;
    }

    public function getInfo( )
    {
        return $this->info;
    }

    public function getWidth( )
    {
        return $this->width;
    }

    public function getHeight( )
    {
        return $this->height;
    }

    private function rgbToHsv( $red, $green, $blue )
    {
        $min    = min( $red, $green, $blue );
        $max    = max( $red, $green, $blue );
        $delta  = $max - $min;

        $h = $s = $v = $max;

        $v = floor( $max / 255 * 100 );
        if ($max != 0) {
            $s = floor( $delta / $max * 100 );
        } else {
            return array( 0, 0, 0 );
        }

        if ($red == $max) {
            $h = ( $green - $blue ) / $delta;
        } elseif ($green == $max) {
            $h = 2 + ( $blue - $red ) / $delta;
        } else {
            $h = 4 + ( $red - $green ) / $delta;
        }

        $h = floor( $h * 60 );
        if ($h < 0) {
            $h += 360;
        }

        return array( $h, $s, $v );

    }

    private function hsvToRgb( $hue, $sat, $val )
    {
        $hue %= 360;

        if ($val == 0) {
            return array( 0, 0, 0 );
        }

        $sat /= 100;
        $val /= 100;
        $hue /= 60;

        $i = floor( $hue );
        $f = $hue - $i;
        $p = $val * ( 1 - $sat);
        $q = $val * ( 1 - ( $sat * $f ) );
        $t = $val * ( 1 - ( $sat * ( 1 - $f ) ) );

        switch ($i) {
            case 0 :
                $red = $val;
                $grn = $t;
                $blu = $p;
                break;
            case 1 :
                $red = $q;
                $grn = $val;
                $blu = $p;
                break;
            case 2 :
                $red = $p;
                $grn = $val;
                $blu = $t;
                break;
            case 3 :
                $red = $p;
                $grn = $q;
                $blu = $val;
                break;
            case 4 :
                $red = $t;
                $grn = $p;
                $blu = $val;
                break;
            case 5 :
                $red = $val;
                $grn = $p;
                $blu = $q;
                break;
        }

        $red = floor( $red * 255 );
        $grn = floor( $grn * 255 );
        $blu = floor( $blu * 255 );

        return array( $red, $grn, $blu );
    }

    private function rgbToHex( $red, $green, $blue )
    {
        return '#' . str_pad( dechex( $red), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $green ), 2, '0', STR_PAD_LEFT ) . str_pad( dechex( $blue ), 2, '0', STR_PAD_LEFT );
    }

    public function load( $path )
    {
        $this->setPath( $path );

        $image_info = getimagesize( $this->path );
        $this->setType( $image_info[ 2 ] );

        if ($this->type == IMAGETYPE_JPEG) {
            $image = imagecreatefromjpeg( $this->path );
        }

        if ($this->type == IMAGETYPE_PNG) {
            $image = imagecreatefrompng( $this->path );
        }

        $this->setImage( $image );

        return $this;
    }

    public function execute( )
    {
        $red_total      = 0;
        $green_total    = 0;
        $blue_total     = 0;
        $total          = 0.0;

        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $rgb    = imagecolorat( $this->image, $x, $y );
                $colors = imagecolorsforindex( $this->image, $rgb );

                $saturation = ( max( $colors[ 'red' ], max( $colors[ 'green' ], $colors[ 'blue' ] ) ) - min( $colors[ 'red' ], min( $colors[ 'green' ], $colors[ 'blue' ] ) ) );
                $relevance  = 0.1 + 0.9 * ( $colors[ 'alpha' ] ) * $saturation;

                $red_total      += $colors[ 'red' ] * $relevance;
                $green_total    += $colors[ 'green' ] * $relevance;
                $blue_total     += $colors[ 'blue' ] * $relevance;
                $total          += $relevance;
            }
        }

        $red            = round( $red_total / $total );
        $green          = round( $green_total / $total );
        $blue           = round( $blue_total / $total );
        $background_rgb = array( $red, $green, $blue );
        $background_hex = $this->rgbToHex( $red, $green, $blue );

        list( $hue, $saturation, $value ) = $this->rgbToHSV( $red, $green, $blue );
        if ($saturation > 0.15) {
            $hsaturation = 65;
        }
        $value      = 90;
        $glow_rgb   = $this->hsvToRgb( $hue, $saturation, $value );
        $glow_hex   = $this->rgbToHex( $glow_rgb[ 0 ], $glow_rgb[ 1 ], $glow_rgb[ 2 ] );

        return  (object) array(
                    'background'    => (object) array(
                        'rgb'   => $background_rgb,
                        'hex'   => $background_hex
                    ),
                    'glow'          => (object) array(
                        'rgb'   => $glow_rgb,
                        'hex'   => $glow_hex
                    )
                );
    }
}