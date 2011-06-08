# PHP Color Utility Class

This class is intended to make it easier to convert between colorspaces,
as well as compare one color to another.

## Examples:

### Initialize object (using hex notation is easier if you are familiar with CSS colors):

    $color = new Color(0xFFFFFF);


### Get distance from another color using the RGB colorspace:

    $color1 = new Color(0xFFFFFF);
    $color2 = new Color(0x888888);

    $distance = $color1->getDistanceRgbFrom($color2);

### Get closest matching color using the Lab(CIE) colorspace:

    $color = new Color(0xFFFFFF);

    $palette = array(
        0x000000,
        0x888888,
        0xAAAAAA
    );

    $matchIndex = $color->getClosestMatch($palette);
    $matchColor = $palette[$matchIndex];