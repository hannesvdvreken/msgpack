# [Msgpack](http://msgpack.org/) implementation for Modern PHP
[![Build Status](http://img.shields.io/travis/hannesvdvreken/msgpack.svg?style=flat-square)](https://travis-ci.org/hannesvdvreken/msgpack)
[![License](http://img.shields.io/packagist/l/hannesvdvreken/msgpack.svg?style=flat-square)](#license)

## What is Msgpack?

If you ever wished to use JSON for convenience (storing an image with metadata)
but could not for technical reasons (encoding, size, speed...), MessagePack is a perfect replacement.

MessagePack is an efficient binary serialization format.
It lets you exchange data among multiple languages like JSON.
But it's faster and smaller.
Small integers are encoded into a single byte,
and typical short strings require only one extra byte in addition to the strings themselves.

For more information on Msgpack: [msgpack.org](http://msgpack.org/)

## Usage

There are 2 main classes with each a single method to use:

```php
$encoder = new Msgpack\Encoder();
$string = $encoder->encode($data);

$decoder = new Msgpack\Decoder();
$data = $decoder->decode($string);
```

Or if you prefer, you can also call the static methods with the exact same interface:

```php
$string = Msgpack\Encoder::encode($data);

$decoder = Msgpack\Decoder::decode($string);
```

This is to allow you to inject the encoder and decoder into your classes so you can mock them out.

Some messages or encoded string throw exceptions because Msgpack has its limitations:

```php
try {
    $string = $encoder->encode($data);
} catch (Msgpack\UnencodeableException $unencex) {
    //
}
```

```php
try {
    $string = $decoder->decode($string);
} catch (Msgpack\UndecodeableException $undecex) {
    //
}
```

## Testing

Run tests with `composer test`.

## Other packages

This is [not the first](https://packagist.org/search/?q=msgpack) packagist package on msgpack,
but it is the first that is not an RPC client for the Msgpack cli tool. This package doesn't require
any external non-php libraries to be installed. The [default Msgpack library](https://github.com/msgpack/msgpack-php)
is a PECL library, so not so interesting to install.

## License

[MIT](license)
