<?php
namespace Msgpack;

use BadMethodCallException;

class Encoder
{
    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return string
     */
    public function __call($name, $arguments)
    {
        if ($name != 'encode') {
            throw new BadMethodCallException();
        }

        return self::encode($arguments[0]);
    }

    /**
     * @param mixed $data
     *
     * @return string
     * @throws Exception
     * @throws UnencodeableException
     */
    public static function encode($data)
    {
        switch (gettype($data)) {
            case 'array':
                return self::encodeArray($data);
            case 'object':
                return self::encodeObject($data);
            case 'string':
                return self::encodeString($data);
            case 'integer':
                return self::encodeInteger($data);
            case 'boolean':
                return self::encodeBoolean($data);
            case 'double':
                return self::encodeDouble($data);
            case 'NULL':
                return self::encodeNull($data);
            case 'resource':
                // TODO
                break;
            default:
                throw new UnencodeableException('Contains unencodeable type', 3);
        }
    }

    /**
     * @param array $array
     *
     * @return string
     * @throws UnencodeableException
     */
    private static function encodeArray(array $array)
    {
        // If it is an associative array, treat it as an object.
        if (!empty($array) && array_keys($array) !== range(0, count($array) - 1)) {
            return self::encodeMap($array);
        }

        // Differentiate on the array length.
        if (count($array) < 16) {
            // fixarray
            $prefix = bindec(1001) * 16 + count($array);
        } elseif (count($array) < pow(2, 16)) {
            // array 16
            $prefix = 0xdc * 256 * 256;
            $prefix += count($array);
        } elseif (count($array) < pow(2, 32)) {
            // array 32
            $prefix = 0xdd * 256 * 256 * 256 * 256;
            $prefix += count($array);
        } else {
            throw new UnencodeableException('Array with more then 2^32 elements included cannot be encoded.', 1);
        }

        // Byte encode it.
        $result = pack('H*', dechex($prefix));

        // Loop every item of the array.
        foreach (array_values($array) as $item) {
            // Concatenate everything.
            $result .= self::encode($item);
        }

        // Return the result.
        return $result;
    }

    /**
     * @param $object
     *
     * @return string
     */
    private static function encodeObject($object)
    {
        // Convert to associative array and encode that.
        return self::encodeMap(get_object_vars($object));
    }

    /**
     * @param array $array
     *
     * @return string
     * @throws UnencodeableException
     */
    private static function encodeMap(array $array)
    {
        // Differentiate on the map length.
        if (count($array) < 16) {
            // fixmap
            $prefix = bindec(1000) * 16 + count($array);
        } elseif (count($array) < pow(2, 16)) {
            // map 16
            $prefix = 0xde * 256 * 256;
            $prefix += count($array);
        } elseif (count($array) < pow(2, 32)) {
            // map 32
            $prefix = 0xdf * 256 * 256 * 256 * 256;
            $prefix += count($array);
        } else {
            $msg = 'Map (object or associative array) with more then 2^32 elements included that cannot be encoded.';
            throw new UnencodeableException($msg, 2);
        }

        // Byte encode it.
        $result = pack('H*', dechex($prefix));

        // Loop every item of the array.
        foreach ($array as $key => $value) {
            // Both encode and append the key and the value.
            $result .= self::encode($key);
            $result .= self::encode($value);
        }

        // Return the result.
        return $result;
    }

    /**
     * @param string $string
     *
     * @return string
     * @throws UnencodeableException
     */
    private static function encodeString($string)
    {
        // Note:
        // strlen() returns the number of bytes rather than the number of characters in a string.

        // Differentiate on the string length.
        if (strlen($string) < 32) {
            // fixstr
            $prefix = bindec(101) * 32 + strlen($string);
        } elseif (strlen($string) < pow(2, 8)) {
            // str 8
            $prefix = 0xd9 * 256 + strlen($string);
        } elseif (strlen($string) < pow(2, 16)) {
            // str 16
            $prefix = 0xda * 256 * 256 + strlen($string);
        } elseif (strlen($string) < pow(2, 32)) {
            // str 32
            $prefix = 0xdb * 256 * 256 * 256 * 256 + strlen($string);
        } else {
            throw new UnencodeableException('String to be encoded is too long', 4);
        }

        // Encode it and concatenate with all bytes of the string value.
        return pack('H*', dechex($prefix)) . $string;
    }

    /**
     * @param int $integer
     *
     * @return string
     */
    private static function encodeInteger($integer)
    {
        if ($integer >= 0 && $integer < pow(2, 7)) {
            // positive fixnum: 7-bit positive integer
            // 0XXXXXXX
            return pack('H*', str_pad(dechex($integer), 2, 0, STR_PAD_LEFT));
        } elseif ($integer < 0 && abs($integer) < pow(2, 5)) {
            // negative fixnum: 5-bit negative integer
            // 111YYYYY
            return pack('H*', dechex(bindec(11100000) + $integer));
        }

        if ($integer >= 0) {
            $prefixes = array(8 => 0xcc00, 16 => 0xcd0000, 32 => 0xce00000000, 64 => 0xcf);
        } else {
            $prefixes = array(8 => 0xd000, 16 => 0xd10000, 32 => 0xd200000000, 64 => 0xd3);
        }

        // For each step the max size increases.
        foreach (array(8, 16, 32) as $power) {
            if ($integer < pow(2, $power)) {
                return pack('H*', dechex($prefixes[$power] + $integer));
            }
        }

        // 0x** ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ
        return pack('H*', dechex($prefixes[64]).str_pad(dechex($integer), 16, '0', STR_PAD_LEFT));
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private static function encodeDouble($data)
    {
        throw new Exception('Not implemented');
    }

    /**
     * @param bool $boolean
     *
     * @return string
     */
    private static function encodeBoolean($boolean)
    {
        if ($boolean) {
            $value = 0xc3;
        } else {
            $value = 0xc2;
        }

        return pack('H*', dechex($value));
    }

    /**
     * @param $data
     *
     * @return string
     */
    private static function encodeNull($data)
    {
        return pack('H*', dechex(0xc0));
    }
}
