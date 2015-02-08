<?php
/**
 * Color utility and conversion
 * 
 * Represents a color value, and converts between RGB/HSV/XYZ/Lab
 * 
 * Example:
 * $color = new Color(0xFFFFFF);
 * 
 * @author Harold Asbridge <hasbridge@gmail.com>
 */
class Color
{
    /**
     * @var int
     */
    protected $color = 0;
    
    /**
     * Initialize object
     * 
     * @param int $color An integer color, such as a return value from imagecolorat()
     */
    public function __construct($intColor = null)
    {
        if ($intColor) {
            $this->fromInt($intColor);
        }
    }
    
    /**
     * Init color from hex value
     * 
     * @param string $hexValue
     * 
     * @return Color
     */
    public function fromHex($hexValue)
    {
        $this->color = hexdec($hexValue);
        
        return $this;
    }
    
    /**
     * Init color from integer RGB values
     * 
     * @param int $red
     * @param int $green
     * @param int $blue
     * 
     * @return Color 
     */
    public function fromRgbInt($red, $green, $blue)
    {
        $this->color = (int)(($red << 16) + ($green << 8) + $blue);
        
        return $this;
    }
    
    /**
     * Init color from hex RGB values
     * 
     * @param string $red
     * @param string $green
     * @param string $blue
     * 
     * @return Color
     */
    public function fromRgbHex($red, $green, $blue)
    {
        return $this->fromRgbInt(hexdec($red), hexdec($green), hexdec($blue));
    }

    /**
     * Init color from a hex RGB string (#00FF00)
     *
     * @param $hex
     * @return Color
     */
    public function fromRgbString($hex)
    {
        $hex = str_replace("#", "", $hex);

        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }

        return $this->fromRgbInt($r, $g, $b);
    }
    
    /**
     * Init color from integer value
     * 
     * @param int $intValue
     * 
     * @return Color
     */
    public function fromInt($intValue)
    {
        $this->color = $intValue;
        
        return $this;
    }

    /**
     * Init color from a CIE XYBri value
     *
     * @param $x
     * @param $y
     * @param $brightness
     * @return Color
     */
    public function fromXYBri($x, $y, $brightness)
    {
        $_x = ($x * $brightness) / $y;
        $_y = $brightness;
        $_z = ((1 - $x - $y) * $brightness) / $y;

        $r = $_x * 3.2406 + $_y * -1.5372 + $_z * -0.4986;
        $g = $_x * -0.9689 + $_y * 1.8758 + $_z * 0.0415;
        $b = $_x * 0.0557 + $_y * -0.2040 + $_z * 1.0570;

        $r = $r > 0.0031308 ? 1.055 * pow($r, 1 / 2.4) - 0.055 : 12.92 * $r;
        $g = $g > 0.0031308 ? 1.055 * pow($g, 1 / 2.4) - 0.055 : 12.92 * $g;
        $b = $b > 0.0031308 ? 1.055 * pow($b, 1 / 2.4) - 0.055 : 12.92 * $b;

        $r = $r > 0 ? round($r * 255) : 0;
        $g = $g > 0 ? round($g * 255) : 0;
        $b = $b > 0 ? round($b * 255) : 0;

        return $this->fromRgbInt($r, $g, $b);
    }


    /**
     * Convert color to hex
     * 
     * @return string
     */
    public function toHex()
    {
        return str_pad(dechex($this->color),6,"0",STR_PAD_LEFT);
    }
    
    /**
     * Convert color to RGB array (integer values)
     * 
     * @return array
     */
    public function toRgbInt()
    {
        return array(
            'red'   => (int)(255 & ($this->color >> 16)),
            'green' => (int)(255 & ($this->color >> 8)),
            'blue'  => (int)(255 & ($this->color))
        );
    }

    /**
     * Convert color to RGB array (hex values)
     * 
     * @return array
     */
    public function toRgbHex()
    {
        return array_map(function($item){
            return dechex($item);
        }, $this->toRgbInt());
    }

    /**
     * Convert color to an RGB Hex string (#00FF00)
     *
     * @return string
     */
    public function toRgbString()
    {
        $hexes = $this->toRgbHex();
        array_walk($hexes, function(&$hex, $key){
            $hex = str_pad($hex,2,"0",STR_PAD_LEFT);
        });
        return "#" . implode('', $hexes);
    }
    
    /**
     * Get Hue/Saturation/Value for the current color 
     * (float values, slow but accurate)
     * 
     * @return array
     */
    public function toHsvFloat()
    {
        $rgb = $this->toRgbInt();
        
        $rgbMin = min($rgb);
        $rgbMax = max($rgb);
        
        $hsv = array(
            'hue'   => 0,
            'sat'   => 0,
            'val'   => $rgbMax
        );
        
        // If v is 0, color is black
        if ($hsv['val'] == 0) {
            return $hsv;
        }
        
        // Normalize RGB values to 1
        $rgb['red'] /= $hsv['val'];
        $rgb['green'] /= $hsv['val'];
        $rgb['blue'] /= $hsv['val'];
        $rgbMin = min($rgb);
        $rgbMax = max($rgb);
        
        // Calculate saturation
        $hsv['sat'] = $rgbMax - $rgbMin;
        if ($hsv['sat'] == 0) {
            $hsv['hue'] = 0;
            return $hsv;
        }
        
        // Normalize saturation to 1
        $rgb['red'] = ($rgb['red'] - $rgbMin) / ($rgbMax - $rgbMin);
        $rgb['green'] = ($rgb['green'] - $rgbMin) / ($rgbMax - $rgbMin);
        $rgb['blue'] = ($rgb['blue'] - $rgbMin) / ($rgbMax - $rgbMin);
        $rgbMin = min($rgb);
        $rgbMax = max($rgb);
        
        // Calculate hue
        if ($rgbMax == $rgb['red']) {
            $hsv['hue'] = 0.0 + 60 * ($rgb['green'] - $rgb['blue']);
            if ($hsv['hue'] < 0) {
                $hsv['hue'] += 360;
            }
        } else if ($rgbMax == $rgb['green']) {
            $hsv['hue'] = 120 + (60 * ($rgb['blue'] - $rgb['red']));
        } else {
            $hsv['hue'] = 240 + (60 * ($rgb['red'] - $rgb['green']));
        }
        
        return $hsv;
    }
    
    /**
     * Get HSV values for color
     * (integer values from 0-255, fast but less accurate)
     * 
     * @return int 
     */
    public function toHsvInt()
    {
        $rgb = $this->toRgbInt();
        
        $rgbMin = min($rgb);
        $rgbMax = max($rgb);
        
        $hsv = array(
            'hue'   => 0,
            'sat'   => 0,
            'val'   => $rgbMax
        );
        
        // If value is 0, color is black
        if ($hsv['val'] == 0) {
            return $hsv;
        }
        
        // Calculate saturation
        $hsv['sat'] = round(255 * ($rgbMax - $rgbMin) / $hsv['val']);
        if ($hsv['sat'] == 0) {
            $hsv['hue'] = 0;
            return $hsv;
        }
        
        // Calculate hue
        if ($rgbMax == $rgb['red']) {
            $hsv['hue'] = round(0 + 43 * ($rgb['green'] - $rgb['blue']) / ($rgbMax - $rgbMin));
        } else if ($rgbMax == $rgb['green']) {
            $hsv['hue'] = round(85 + 43 * ($rgb['blue'] - $rgb['red']) / ($rgbMax - $rgbMin));
        } else {
            $hsv['hue'] = round(171 + 43 * ($rgb['red'] - $rgb['green']) / ($rgbMax - $rgbMin));
        }
        if ($hsv['hue'] < 0) {
            $hsv['hue'] += 255;
        }
        
        return $hsv;
    }
    
    /**
     * Get current color in XYZ format
     * 
     * @return array
     */
    public function toXyz()
    {
        $rgb = $this->toRgbInt();
        
        // Normalize RGB values to 1
        $rgb = array_map(function($item){
            return $item / 255;
        }, $rgb);
        
        $rgb = array_map(function($item){
            if ($item > 0.04045) {
                $item = pow((($item + 0.055) / 1.055), 2.4);
            } else {
                $item = $item / 12.92;
            }
            return ($item * 100);
        }, $rgb);
        
        //Observer. = 2°, Illuminant = D65
        $xyz = array(
            'x' => ($rgb['red'] * 0.4124) + ($rgb['green'] * 0.3576) + ($rgb['blue'] * 0.1805),
            'y' => ($rgb['red'] * 0.2126) + ($rgb['green'] * 0.7152) + ($rgb['blue'] * 0.0722),
            'z' => ($rgb['red'] * 0.0193) + ($rgb['green'] * 0.1192) + ($rgb['blue'] * 0.9505)
        );
        
        return $xyz;
    }
    
    /**
     * Get color CIE-Lab values
     * 
     * @return array
     */
    public function toLabCie()
    {
        $xyz = $this->toXyz();
        
        //Ovserver = 2*, Iluminant=D65
        $xyz['x'] /= 95.047;
        $xyz['y'] /= 100;
        $xyz['z'] /= 108.883;
        
        $xyz = array_map(function($item){
            if ($item > 0.008856) {
                //return $item ^ (1/3);
                return pow($item, 1/3);
            } else {
                return (7.787 * $item) + (16 / 116);
            }
        }, $xyz);
        
        $lab = array(
            'l' => (116 * $xyz['y']) - 16,
            'a' => 500 * ($xyz['x'] - $xyz['y']),
            'b' => 200 * ($xyz['y'] - $xyz['z'])
        );
        
        return $lab;
    }

    /**
     * Convert color to integer
     * 
     * @return int
     */
    public function toInt()
    {
        return $this->color;
    }

    /**
     * Convert color to a CIE XYBri array.
     *
     * @return array
     * @throws Exception
     */
    public function toXYBri(){
        $rgb = $this->toRgbInt();

        $r = $rgb['red'];
        $g = $rgb['green'];
        $b = $rgb['blue'];

        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        if ($r < 0 || $r > 1 || $g < 0 || $g > 1 || $b < 0 || $b > 1) {
            throw new \Exception("Invalid RGB array. [{$r},{$b},{$g}]");
        }

        $rt = ($r > 0.04045) ? pow(($r + 0.055) / (1.0 + 0.055), 2.4) : ($r / 12.92);
        $gt = ($g > 0.04045) ? pow(($g + 0.055) / (1.0 + 0.055), 2.4) : ($g / 12.92);
        $bt = ($b > 0.04045) ? pow(($b + 0.055) / (1.0 + 0.055), 2.4) : ($b / 12.92);

        $cie_x = $rt * 0.649926 + $gt * 0.103455 + $bt * 0.197109;
        $cie_y = $rt * 0.234327 + $gt * 0.743075 + $bt * 0.022598;
        $cie_z = $rt * 0.0000000 + $gt * 0.053077 + $bt * 1.035763;

        $hue_x = $cie_x / ($cie_x + $cie_y + $cie_z);
        $hue_y = $cie_y / ($cie_x + $cie_y + $cie_z);

        return array('x'=>$hue_x,'y'=>$hue_y,'bri'=>$cie_y);
    }


    /**
     * Alias of toString()
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
    
    /**
     * Get color as string
     * 
     * @return string
     */
    public function toString()
    {
        $str = (string)$this->toHex();
        if (strlen($str) < 6) {
            $str = str_pad($str, 6, '0', STR_PAD_LEFT);
        }
        return strtoupper("#{$str}");
    }
    
    /**
     * Get the distance between this color and the given color
     * 
     * @param Color $color 
     * 
     * @return int
     */
    public function getDistanceRgbFrom(Color $color)
    {
        $rgb1 = $this->toRgbInt();
        $rgb2 = $color->toRgbInt();
        
        $rDiff = abs($rgb1['red'] - $rgb2['red']);
        $gDiff = abs($rgb1['green'] - $rgb2['green']);
        $bDiff = abs($rgb1['blue'] - $rgb2['blue']);
        
        // Sum of RGB differences
        $diff = $rDiff + $gDiff + $bDiff;
        return $diff;
    }
    
    /**
     * Get distance from the given color using the Delta E method
     * 
     * @param Color $color 
     * 
     * @return float
     */
    public function getDistanceLabFrom(Color $color)
    {
        $lab1 = $this->toLabCie();
        $lab2 = $color->toLabCie();
        
        $lDiff = abs($lab2['l'] - $lab1['l']);
        $aDiff = abs($lab2['a'] - $lab1['a']);
        $bDiff = abs($lab2['b'] - $lab1['b']);
        
        $delta = sqrt($lDiff + $aDiff + $bDiff);
        
        return $delta;
    }
    
    /**
     * Detect if color is grayscale
     * 
     * @param int $threshold
     * 
     * @return bool
     */
    public function isGrayscale($threshold = 16)
    {
        $rgb = $this->toRgbInt();
        
        // Get min and max rgb values, then difference between them
        $rgbMin = min($rgb);
        $rgbMax = max($rgb);
        $diff = $rgbMax - $rgbMin;
        
        return $diff < $threshold;
    }
    
    /**
     * Get the closest matching color from the given array of colors
     * 
     * @param array $colors array of integers or Color objects
     * 
     * @return mixed the array key of the matched color
     */
    public function getClosestMatch(array $colors)
    {
        $matchDist = 10000;
        $matchKey = null;
        foreach($colors as $key => $color) {
            if (false === ($color instanceof Color)) {
                $c = new Color($color);
            }
            $dist = $this->getDistanceLabFrom($c);
            if ($dist < $matchDist) {
                $matchDist = $dist;
                $matchKey = $key;
            }
        }
        
        return $matchKey;
    }
}
