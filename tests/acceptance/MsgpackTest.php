<?php

use Msgpack\Encoder;

class MsgpackTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Encoder
     */
    protected $encoder;

    /**
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->encoder = new Encoder();
        parent::__construct($name, $data, $dataName);
    }

    /**
     * @test
     * @dataProvider provideMsgpackData
     * @param mixed $input
     */
    public function testEquals($input)
    {
        $result = $this->encoder->encode($input);
        $expected = msgpack_pack($input);

        $this->assertEquals($result, $expected);
    }

    /**
     * @return array
     */
    public function provideMsgpackData()
    {
        $arr = [];

        // Strings.
        $arr[] = '';
        $arr[] = 'a';
        $arr[] = 'abc';
        $arr[] = join('', range('a', 'z'));
        $arr[] = str_repeat('a', 2048);

        // Booleans and null.
        $arr[] = true;
        $arr[] = false;
        $arr[] = null;

        // Integers.
        $arr[] = 0;
        $arr[] = 5;
        $arr[] = 5000;
        $arr[] = PHP_INT_MAX;

        // Negative integers.
        //$arr[] = -1;
        //$arr[] = -5;
        //$arr[] = -1000000;

        // Arrays.
        $arr[] = [];
        $arr[] = ['foo', 'bar', 'baz'];
        $arr[] = array_merge(range('a', 'z'), range('A', 'Z'));

        // Associative arrays.
        $arr[] = ['foo' => 'bar'];
        $arr[] = ['foo' => ['bar' => 'baz']];
        $arr[] = ['foo' => ['bar' => ['baz' => 'qux']]];
        $arr[] = [['bar' => 'bar'], ['baz' => 'qux']];

        // Objects.
        //$obj = new stdClass();
        //$obj->foo = 'bar';
        //$obj->bar = 1024;
        //$obj->baz = false;
        //$obj->qux = null;
        //$arr[] = $obj;
        //$obj = new stdClass();
        //$obj->foo = new stdClass();
        //$arr[] = $obj;

        return array_map(function ($item) {
            return [null => $item];
        }, $arr);
    }
}