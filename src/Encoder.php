<?php
namespace Msgpack;

class Encoder
{
    /**
     * @param $data
     *
     * @return string
     * @throws UnencodeableException
     */
    public function encode($data)
    {
        switch (gettype($data)) {
            case 'array':
                return $this->encodeArray($data);
                break;
            case 'object':
                return $this->encodeObject($data);
                break;
            case 'string':
                return $this->encodeString($data);
                break;
            case 'integer':
                return $this->encodeInteger($data);
                break;
            case 'boolean':
                return $this->encodeBoolean($data);
                break;
            case 'double':
                return $this->encodeDouble($data);
                break;
            case 'NULL':
                return $this->encodeNull($data);
                break;
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
    private function encodeArray(array $array)
    {
        // If it is an associative array, treat it as an object.
        if (!empty($array) && array_keys($array) !== range(0, count($array) - 1)) {
            return $this->encodeMap($array);
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
            $result .= $this->encode($item);
        }

        // Return the result.
        return $result;
    }

    /**
     * @param $object
     *
     * @return string
     */
    private function encodeObject($object)
    {
        // Convert to associative array and encode that.
        return $this->encodeMap(get_object_vars($object));
    }

    /**
     * @param array $array
     *
     * @return string
     * @throws UnencodeableException
     */
    private function encodeMap(array $array)
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
            $result .= $this->encode($key);
            $result .= $this->encode($value);
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
    private function encodeString($string)
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
    private function encodeInteger($integer)
    {
        if ($integer >= 0) {
            // All positive integers.
            if ($integer < pow(2, 7)) {
                // positive fixnum: 7-bit positive integer
                // 0XXXXXXX
                return pack('H*', str_pad(dechex($integer), 2, 0, STR_PAD_LEFT));
            } elseif ($integer < pow(2, 8)) {
                // uint 8: 8-bit unsigned integer
                // 0xcc ZZZZZZZZ
                return pack('H*', dechex(0xcc00 + $integer));
            } elseif ($integer < pow(2, 16)) {
                // uint 16: 16-bit big-endian unsigned integer
                // 0xcd ZZZZZZZZ ZZZZZZZZ
                return pack('H*', dechex(0xcd0000 + $integer));
            } elseif ($integer < pow(2, 32)) {
                // uint 32: 32-bit big-endian unsigned integer
                // 0xce ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ
                return pack('H*', dechex(0xce00000000 + $integer));
            } elseif ($integer < pow(2, 64)) {
                // uint 64: 64-bit big-endian unsigned integer
                // 0xcf ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ
                return pack('H*', dechex(0xcf) . str_pad(dechex($integer), 16, '0', STR_PAD_LEFT));
            }
        } else {
            // Negative integers.
            $integer = abs($integer);

            if ($integer < pow(2, 5)) {
                // negative fixnum: 5-bit negative integer
                // 111YYYYY
                return pack('H*', dechex(bindec(11100000) + $integer));
            } elseif ($integer < pow(2, 8)) {
                // int 8: 8-bit signed integer
                // 0xd0 ZZZZZZZZ
                return pack('H*', dechex(0xd000 + $integer));
            } elseif ($integer < pow(2, 16)) {
                // int 16: 16-bit big-endian signed integer
                // 0xd1 ZZZZZZZZ ZZZZZZZZ
                return pack('H*', dechex(0xd10000 + $integer));
            } elseif ($integer < pow(2, 32)) {
                // int 32: 32-bit big-endian signed integer
                // 0xd2 ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ
                return pack('H*', dechex(0xd200000000 + $integer));
            } elseif ($integer < pow(2, 64)) {
                // int 64: 64-bit big-endian signed integer
                // 0xd3 ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ ZZZZZZZZ
                return pack('H*', dechex(0xd3) . str_pad(dechex($integer), 16, '0', STR_PAD_LEFT));
            }
        }
    }

    /**
     * @param $data
     *
     * @throws Exception
     */
    private function encodeDouble($data)
    {
        throw new Exception('Not implemented');
    }

    /**
     * @param bool $boolean
     *
     * @return string
     */
    private function encodeBoolean($boolean)
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
    private function encodeNull($data)
    {
        return pack('H*', dechex(0xc0));
    }
}
