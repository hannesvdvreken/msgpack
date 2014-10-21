<?php
namespace Msgpack;

class Decoder
{
    /**
     * @param string $string
     * @param bool   $assoc
     *
     * @throws UndecodeableException
     * @return mixed
     */
    public function decode($string, $assoc = false)
    {
        return reset($this->decodeRecursive($string, $assoc));
    }

    /**
     * @param string $string
     * @param bool   $assoc
     *
     * @return array
     * @throws UndecodeableException
     */
    public function decodeRecursive($string, $assoc = false)
    {
        $first = $string[0];

        switch (true) {
            case ($first >> 4) === pack('H*', dechex(8)): // fixmap 1000xxxx
            case $first === pack('H*', dechex(0xde)): // map 16
            case $first === pack('H*', dechex(0xdf)): // map 32
                return $this->decodeMap($string, $assoc);
                break;
            case ($first >> 4) === pack('H*', dechex(9)): // fixarray 1001xxxx
            case $first === pack('H*', dechex(0xdc)): // array 16
            case $first === pack('H*', dechex(0xdd)): // array 32
                return $this->decodeArray($string, $assoc);
                break;
            case ($first >> 7) === pack('H*', dechex(0)): // fixint 0xxxxxxx
            case ($first >> 5) === pack('H*', dechex(7)): // negative fixint 111xxxxx
            case $first == pack('H*', dechex(0xcc)): // uint 8
            case $first == pack('H*', dechex(0xcd)): // uint 16
            case $first == pack('H*', dechex(0xce)): // uint 32
            case $first == pack('H*', dechex(0xcf)): // uint 64
            case $first == pack('H*', dechex(0xd0)): // int 8
            case $first == pack('H*', dechex(0xd1)): // int 16
            case $first == pack('H*', dechex(0xd2)): // int 32
            case $first == pack('H*', dechex(0xd3)): // int 64
                return $this->decodeInt($string);
                break;
            case ($first >> 5) === pack('H*', dechex(5)): // fixstr 101xxxxx
            case $first == pack('H*', dechex(0xd9)): // str 8
            case $first == pack('H*', dechex(0xda)): // str 16
            case $first == pack('H*', dechex(0xdb)): // str 32
                return $this->decodeString($string);
                break;
            case $first === pack('H*', dechex(0xc2)): // false
                return [false, substr($string, 1)];
            case $first === pack('H*', dechex(0xc3)): // true
                return [true, substr($string, 1)];
            case $first === pack('H*', dechex(0xc0)): // nil
                return [null, substr($string, 1)];
            default:
                throw new UndecodeableException($first . ' is not a valid identifier for a type.', 1);
        }
    }

    private function decodeMap($string, $assoc)
    {
        $first = $string[0];

        if (($first >> 4) === pack('H*', dechex(8))) {
            // fixmap 1000xxxx

        } elseif ($first === pack('H*', dechex(0xde))) {
            // map 16
        } elseif ($first === pack('H*', dechex(0xdf))) {
            // map 32
        }
    }

    private function decodeArray($string, $assoc)
    {
        // TODO
    }

    private function decodeInt($string)
    {
        // TODO
    }

    private function decodeString($string)
    {
        // TODO
    }
}
