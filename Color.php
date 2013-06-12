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
     * @param int @threshold
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
