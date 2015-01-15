<?php
namespace Msgpack;

use BadMethodCallException;
use stdClass;

class Decoder
{
    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if ($name != 'decode') {
            throw new BadMethodCallException();
        }

        return self::decode($arguments[0], $arguments[1]);
    }

    /**
     * @param string $string
     * @param bool   $assoc
     *
     * @throws UndecodeableException
     * @return mixed
     */
    public static function decode($string, $assoc = true)
    {
        return self::decodeRecursive($string, $assoc)[0];
    }

    /**
     * @param string $string
     * @param bool   $assoc
     *
     * @return array
     * @throws UndecodeableException
     */
    public static function decodeRecursive($string, $assoc = false)
    {
        list($firstHex, $firstOctal) = self::analyzeFirstChar($string[0]);

        switch (true) {
            case $firstOctal === '8': // fixmap 1000xxxx
            case $firstHex === 'de': // map 16
            case $firstHex === 'df': // map 32
                return self::decodeMap($string, $assoc);
            case $firstOctal === '9': // fixarray 1001xxxx
            case $firstHex === 'dc': // array 16
            case $firstHex === 'dd': // array 32
                return self::decodeArray($string, $assoc);
            case hexdec($firstHex) < pow(2, 7): // fixint 0xxxxxxx
            case hexdec($firstOctal) > 13: // negative fixint 111xxxxx
            case $firstHex === 'cc': // uint 8
            case $firstHex === 'cd': // uint 16
            case $firstHex === 'ce': // uint 32
            case $firstHex === 'cf': // uint 64
            case $firstHex === 'd0': // int 8
            case $firstHex === 'd1': // int 16
            case $firstHex === 'd2': // int 32
            case $firstHex === 'd3': // int 64
                return self::decodeInt($string);
            case in_array(hexdec($firstOctal), [10, 11]): // fixstr 101xxxxx
            case $firstHex === 'd9': // str 8
            case $firstHex === 'da': // str 16
            case $firstHex === 'db': // str 32
                return self::decodeString($string);
            case $firstHex === 'c2': // false
                return [false, substr($string, 1)];
            case $firstHex === 'c3': // true
                return [true, substr($string, 1)];
            case $firstHex === 'c0': // nil
                return [null, substr($string, 1)];
            default:
                throw new UndecodeableException($firstHex . ' is not a valid identifier for a type.', 1);
        }
    }

    /**
     * @param $string
     * @param $assoc
     *
     * @return array|stdClass
     * @throws UndecodeableException
     */
    private static function decodeMap($string, $assoc)
    {
        $size = 0;
        $offset = 1;
        list($firstHex, $firstOctal) = self::analyzeFirstChar($string[0]);

        if ($firstOctal === '8') {
            // fixmap 1000xxxx
            $size = hexdec($firstHex) - bindec('10000000');
            $offset = 1;
        } elseif ($firstHex === 'de') {
            // map 16
            $size = hexdec(unpack('H*', substr($string, 1, 2)));
            $offset = 3;
        } elseif ($firstHex === 'df') {
            // map 32
            $size = hexdec(unpack('H*', substr($string, 1, 4)));
            $offset = 5;
        }

        // Strip off map data prefix.
        $string = substr($string, $offset);

        // Differentiate between an associative array and an object.
        if ($assoc) {
            $data = [];
            $assign = function ($key, $value) use (&$data) {
                $data[$key] = $value;
            };
        } else {
            $data = new stdClass;
            $assign = function ($key, $value) use ($data) {
                $data->$key = $value;
            };
        }

        // Recursively decode everything in this hashmap.
        for ($i = 0; $i < $size; $i++) {
            list($key, $string) = self::decodeRecursive($string, $assoc);
            list($value, $string) = self::decodeRecursive($string, $assoc);

            $assign($key, $value);
        }

        // Return the result.
        return [$data, $string];
    }

    /**
     * @param $string
     * @param $assoc
     *
     * @return array
     * @throws UndecodeableException
     */
    private static function decodeArray($string, $assoc)
    {
        // Start empty.
        $arr = [];
        $size = 0;
        $offset = 1;

        // Get some information on the first byte.
        list($firstHex, $firstOctal) = self::analyzeFirstChar($string[0]);

        if ($firstOctal === '9') {
            // fixarray 1000xxxx
            $size   = hexdec(substr($firstHex, 1));
            $offset = 1;
        } elseif ($firstHex === 'dc') {
            // arrar 16
            $size   = hexdec(unpack('H*', substr($string, 1, 2)));
            $offset = 3;
        } elseif ($firstHex === 'dd') {
            // array 32
            $size = hexdec(unpack('H*', substr($string, 1, 4)));
            $offset = 5;
        }

        // Shorten the string.
        $string = substr($string, $offset);

        // Loop to decode every item of the array.
        for ($i = 0; $i < $size; $i++) {
            list($item, $string) = self::decodeRecursive($string, $assoc);
            $arr[] = $item;
        }

        // Return the decoded data and the resulting string.
        return [$arr, $string];
    }

    /**
     * @param $string
     *
     * @return array
     */
    private static function decodeInt($string)
    {
        // Get info on the first char.
        list($firstHex, $firstOctal) = self::analyzeFirstChar($string[0]);

        if (hexdec($firstHex) < pow(2, 7)) {
            // fixint 0xxxxxxx
            $int = hexdec($firstHex);
            return [$int, substr($string, 1)];
        } elseif (hexdec($firstOctal) > 13) {
            // negative fixint 111xxxxx
            // TODO
        } elseif ($firstHex === 'cc') {
            // uint 8
            // TODO
        } elseif ($firstHex === 'cd') {
            // uint 16
            // TODO
        } elseif ($firstHex === 'ce') {
            // uint 32
            // TODO
        } elseif ($firstHex === 'cf') {
            // uint 64
            // TODO
        } elseif ($firstHex === 'd0') {
            // int 8
            // TODO
        } elseif ($firstHex === 'd1') {
            // int 16
            // TODO
        } elseif ($firstHex === 'd2') {
            // int 32
            // TODO
        } elseif ($firstHex === 'd3') {
            // int 64
            // TODO
        }
        return [0, substr($string, 1)];
    }

    /**
     * @param $string
     *
     * @return array
     */
    private static function decodeString($string)
    {
        $size = 0;
        $offset = 1;

        // Get some information on the first character.
        list($firstHex, $firstOctal) = self::analyzeFirstChar($string[0]);

        if (in_array(hexdec($firstOctal), [10, 11])) {
            // fixstr
            $size   = hexdec($firstHex) - bindec('10100000');
            $offset = 1;
        } elseif ($firstHex === 'd9') {
            // str 8
            $size   = hexdec(unpack('H*', substr($string, 1, 1)));
            $offset = 2;
        } elseif ($firstHex === 'da') {
            // str 16
            $size   = hexdec(unpack('H*', substr($string, 1, 2)));
            $offset = 3;
        } elseif ($firstHex === 'db') {
            // str 32
            $size   = hexdec(unpack('H*', substr($string, 1, 4)));
            $offset = 5;
        }

        // Parse off the first $size characters and store it as the data.
        $string = substr($string, $offset);
        $data   = substr($string, 0, $size);
        $string = substr($string, $size);

        // Return
        return [$data, $string];
    }

    /**
     * @param string $char
     *
     * @return array
     */
    private static function analyzeFirstChar($char)
    {
        $firstHex   = unpack('H*', $char)[1];
        $firstOctal = substr($firstHex, 0, 1);

        return [$firstHex, $firstOctal];
    }
}
