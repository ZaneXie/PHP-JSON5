## PHP-JSON5

JSON5 parser written in PHP. Based directly off of [https://github.com/json5/json5/blob/v0.5.0/lib/json5.js](https://github.com/json5/json5/blob/v0.5.0/lib/json5.js);

## About JSON5 

Official website: http://json5.org/
Official repository in javascript: https://github.com/json5/json5

## Example

There are two ways to use this library.

1. Copy src/json5.php to your project, write this:

    require('json5.php');
    $str = "{foo: 'bar',while: true}";
    $obj = json5_decode($str);
    var_dump($obj);
    
2. Install by composer (TBA)
    
## TEST (TBA)