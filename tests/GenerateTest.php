<?php
/**
 * Created by PhpStorm.
 * User: Matthew Baggett
 * Date: 08/02/2015
 * Time: 09:44
 */

class HexTest extends PHPUnit_Framework_TestCase {
  const accuracy_loss_limit = 500;
  const step_density = 12;

  public function testHexInput(){
    $color = new Color();
    $this->assertEquals('#000000', $color->fromInt(0)->toRgbString());
    $this->assertEquals('#FF0000', $color->fromInt(hexdec('FF0000'))->toRgbString());
    $this->assertEquals('#00FF00', $color->fromInt(hexdec('00FF00'))->toRgbString());
    $this->assertEquals('#0000FF', $color->fromInt(hexdec('0000FF'))->toRgbString());
    $this->assertEquals('#F0F0F0', $color->fromInt(hexdec('F0F0F0'))->toRgbString());
    $this->assertEquals('#0F0F0F', $color->fromInt(hexdec('0F0F0F'))->toRgbString());
    $this->assertEquals('#123456', $color->fromInt(hexdec('123456'))->toRgbString());
    $this->assertEquals('#654321', $color->fromInt(hexdec('654321'))->toRgbString());
  }

  public function testExport(){
    // Generate an export of data.
    $min = hexdec("000000");
    $max = hexdec("FFFFFF");
    $step = round($max/pow(2,self::step_density));
    #$step = 1;
    $array_of_colours = array();
    #echo "Taking " . ($max - $min) . " colours in {$step} steps\n";
    for($i = $min; $i <= $max; $i = $i + $step){
      $color = new Color();
      $color->fromInt($i);
      $array_of_colours[$i]['hex'] = $color->toRgbString();
      $array_of_colours[$i]['HsvFloat'] = $color->toHsvFloat();
      $array_of_colours[$i]['HsvInt'] = $color->toHsvInt();
      $array_of_colours[$i]['XYBri'] = $color->toXYBri();
    }
    return $array_of_colours;
  }

  /**
   * @depends testExport
   */
  public function testExpectedFields($array_of_colours){
    $element = end($array_of_colours);

    // Test HEX
    $this->assertEquals(7, strlen($element['hex']), "Hex string is long enough");
    $this->assertStringMatchesFormat("#%x", $element['hex'], "Hex string is valid");

    // Test XYBri
    $this->assertArrayHasKey("x", $element['XYBri']);
    $this->assertArrayHasKey("y", $element['XYBri']);
    $this->assertArrayHasKey("bri", $element['XYBri']);
    $this->assertGreaterThanOrEqual(0, $element['XYBri']['x']);
    $this->assertGreaterThanOrEqual(0, $element['XYBri']['y']);
    $this->assertGreaterThanOrEqual(0, $element['XYBri']['bri']);

    // Test HSV Float
    $this->assertArrayHasKey("hue", $element['HsvFloat']);
    $this->assertArrayHasKey("sat", $element['HsvFloat']);
    $this->assertArrayHasKey("val", $element['HsvFloat']);

    // Test HSV Int
    $this->assertArrayHasKey("hue", $element['HsvInt']);
    $this->assertArrayHasKey("sat", $element['HsvInt']);
    $this->assertArrayHasKey("val", $element['HsvInt']);

  }

  /**
   * @depends testExport
   */
  public function testOutput($array_of_colours){
    foreach($array_of_colours as $int => $export) {
      $color_from_hex = new Color();
      $color_from_hex->fromRgbString($export['hex']);

      $color_from_xybri = new Color();
      $color_from_xybri->fromXYBri($export['XYBri']['x'], $export['XYBri']['y'], $export['XYBri']['bri']);

      $distance_rgb_between_hex_and_xybri = $color_from_hex->getDistanceRgbFrom($color_from_xybri);
      $distance_lab_between_hex_and_xybri = $color_from_hex->getDistanceLabFrom($color_from_xybri);

      #if($distance_lab_between_hex_and_xybri > 3.3) {
      #  echo "{$int} => {$export['hex']} ... Distance = {$distance_rgb_between_hex_and_xybri} or {$distance_lab_between_hex_and_xybri} ({$color_from_hex->toRgbString()} vs {$color_from_xybri->toRgbString()})\n";
      #  echo " > {$export['XYBri']['x']}, {$export['XYBri']['y']}, {$export['XYBri']['bri']}\n";
      #  echo "\n";
      #}

      // Assert that the Integer we have matches the one from the hex
      $this->assertEquals($int, $color_from_hex->toInt());

      // Assert that the delta between hex and xybri isn't too vast.
      $this->assertLessThanOrEqual(self::accuracy_loss_limit, $distance_rgb_between_hex_and_xybri, "Assure colour Accuracy loss is less than " . self::accuracy_loss_limit . ". {$int} => {$export['hex']} ... Distance = {$distance_rgb_between_hex_and_xybri} or {$distance_lab_between_hex_and_xybri} ({$color_from_hex->toRgbString()} vs {$color_from_xybri->toRgbString()})");
    }
  }

  public function testIsGrayscale(){
    $colours = array("FFFFFF", "F0F0F0", "BBBBBB", "EFEFEF", "A8A8A8", "0F0F0F");
    foreach($colours as $grayscale_colour){
      $color = new Color();
      $color->fromHex($grayscale_colour);
      $this->assertTrue($color->isGrayscale());
    }
  }

  public function testIsNotGrayscale(){
    $colours = array("FF0000", "FFFF00", "0000FF");
    foreach($colours as $not_grayscale_colour){
      $color = new Color();
      $color->fromHex($not_grayscale_colour);
      $this->assertFalse($color->isGrayscale());
    }
  }

  public function testGetClosestMatchWithColorObjects(){
    $red = new Color();
    $red->fromRgbString("FF0000");
    $green = new Color();
    $green->fromRgbString("00FF00");
    $blue = new Color();
    $blue->fromRgbString("0000FF");

    $options = array($red, $green, $blue);

    $color = new Color();
    $color->fromRgbString("CCFEDD");

    $closest_key = $color->getClosestMatch($options);

    $closest = $options[$closest_key];

    $this->assertEquals($green->toInt(), $closest->toInt(), "Closest should be green");
  }

  public function testGetClosestMatchWithIntegers(){
    $red = 16711680;
    $green = 65280;
    $blue = 255;

    $options = array($red, $green, $blue);

    $color = new Color(14496682);

    $closest_key = $color->getClosestMatch($options);

    $closest = $options[$closest_key];

    $this->assertEquals($red, $closest, "Closest should be red");
  }

  public function testToRgbHex(){
    $color = new Color(14496682);
    $rgb_hex = $color->toRgbHex();

    $this->assertArrayHasKey("red", $rgb_hex);
    $this->assertArrayHasKey("green", $rgb_hex);
    $this->assertArrayHasKey("blue", $rgb_hex);

    $this->assertEquals("dd", $rgb_hex['red']);
    $this->assertEquals("33", $rgb_hex['green']);
    $this->assertEquals("aa", $rgb_hex['blue']);
  }

  public function testFromRgbStringShort(){
    $color = new Color();
    $color->fromRgbString("123");
    $this->assertEquals("#112233", $color->toRgbString());
  }

  public function testFromRgbHex(){
    $color = new Color();
    $color->fromRgbHex("ab", "cd", "ef");
    $this->assertEquals("#ABCDEF", $color->toRgbString());
  }

}
