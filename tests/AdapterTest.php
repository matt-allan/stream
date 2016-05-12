<?php

namespace Yuloh\Stream\Tests;

use Yuloh\Stream\Adapters;

class AdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testDiactorosAdapter()
    {
        $r = fopen('php://memory', 'r+');
        $stream = (new Adapters\DiactorosStreamFactory())->create($r);
        $this->assertInstanceOf(\Zend\Diactoros\Stream::class, $stream);
        $this->assertSame($r, $stream->detach());
    }

    public function testGuzzleAdapter()
    {
        $r = fopen('php://memory', 'r+');
        $stream = (new Adapters\GuzzleStreamFactory())->create($r);
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Stream::class, $stream);
        $this->assertSame($r, $stream->detach());
    }

    public function testSlimAdapter()
    {
        $r = fopen('php://memory', 'r+');
        $stream = (new Adapters\SlimStreamFactory())->create($r);
        $this->assertInstanceOf(\Slim\Http\Stream::class, $stream);
        $this->assertSame($r, $stream->detach());
    }
}
