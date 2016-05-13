# Stream

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

A PSR-7 stream implementation.

## Introduction

This package provides an implementation of `Psr\Http\Message\StreamInterface` for [PSR-7](http://www.php-fig.org/psr/psr-7/).  Sometimes you just need a stream but don't want to depend on an entire PSR-7 implementation.

Let's say you are writing a PSR-7 middleware, and it needs to set a new response body.

If you try to write the data with `$response->getBody()->write()`, there might already be an existing body which is longer than what you want to write.  The only way to make that work is to write padding to the end of the string, which is not ideal.

The only way to create a new response body is to instantiate a stream.  A good way to do this is to require a factory object as a dependency, and use that to create the stream.  The downside is you are adding another setup step that the user will have to configure.

This package lets you provide a default implementation, so your middleware will work without having to setup a stream factory.  Since it's a separate package you don't need to pull in an entire PSR-7 implementation just for streams, and you won't end up with dependency conflicts with the user's PSR-7 implementation.

It also includes an interface and adapters for all of the common PSR-7 implementations, so the user doesn't need to set that up manually.

## Install

Via Composer

``` bash
$ composer require yuloh/stream
```

## Usage

### Factory

The simplest way to use this package is to use the [`StreamFactory`](./src/StreamFactory.php).  The `create` method will create a [`Stream`](./src/Stream.php) from a scalar, string, resource, or object (if it implements either JsonSerializable or __toString).

```php
$stream   = (new StreamFactory())->create('Hello world!');
```

### Constructor

You can also create a stream directly.  You will need to provide a valid [resource](http://php.net/manual/en/language.types.resource.php) as the only argument to the constructor.

```php
use Yuloh\Stream\Stream;

$resource = fopen('php://temp', 'r+');
$stream = new Stream($resource);
```

### Allowing Different implementations

To allow the user to use their own stream, you should typehint against the [`StreamFactoryInterface`](src/StreamFactoryInterface) instead of using a concrete implementation.  This package ships with [adapters}(src/Adapters) for all of the common implementations, so the user can easily use their own stream.

```php
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Yuloh\Stream\StreamFactoryInterface;
use Yuloh\Stream\StreamFactory;

class HelloMiddleware
{
    public function __construct(StreamFactoryInterface $streamFactory = null)
    {
        $this->streamFactory = $streamFactory ?? new StreamFactory();
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $stream   = $this->streamFactory->create('Hello world!');
        $response = $response->withBody($stream);
        return $next($request, $response);
    }
}
```

```php
use Yuloh\Stream\Adapters;

// Usage with default implementation:
new HelloMiddleware();

// Usage with Zend Diactoros:
new HelloMiddleware(new Adapters\DiactorosStreamFactory());

// Usage with Guzzle PSR7:
new HelloMiddleware(new Adapters\GuzzleStreamFactory());

// Usage With Slim Framework:
new HelloMiddleware(new Adapters\SlimStreamFactory());
```

## Testing

``` bash
$ composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/vpre/yuloh/stream.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/yuloh/stream/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/yuloh/stream.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/yuloh/stream.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/yuloh/stream.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/yuloh/stream
[link-travis]: https://travis-ci.org/yuloh/stream
[link-scrutinizer]: https://scrutinizer-ci.com/g/yuloh/stream/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/yuloh/stream
[link-downloads]: https://packagist.org/packages/yuloh/stream
[link-author]: https://github.com/yuloh
[link-contributors]: ../../contributors
