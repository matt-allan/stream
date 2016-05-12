<?php

namespace Yuloh\Stream\Tests;

use Yuloh\Stream\StreamFactory;

class StreamFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateWhenString()
    {
        $stream = (new StreamFactory())->create('hello');
        $this->assertSame(0, $stream->tell(), 'The stream should start at position 0');
        $this->assertSame('hello', $stream->getContents());
    }

    public function testCreateWhenEmptyString()
    {
        $stream = (new StreamFactory())->create('');
        $this->assertSame('', $stream->getContents());
    }

    public function testCreateWhenInt()
    {
        $stream = (new StreamFactory())->create(12);
        $this->assertSame('12', $stream->getContents());
    }

    public function testCreateWhenDouble()
    {
        $stream = (new StreamFactory())->create(12.00);
        $this->assertSame('12', $stream->getContents());
    }

    public function testCreateWhenNull()
    {
        $stream = (new StreamFactory())->create();
        $this->assertSame('', $stream->getContents());
    }

    public function testCreateWhenJsonSerializableObject()
    {
        $stream = (new StreamFactory())->create(new JsonData());
        $this->assertSame(['msg' => 'hello world'], json_decode($stream->getContents(), true));
    }

    public function testCreateWhenObjectImplementingToString()
    {
        $stream = (new StreamFactory())->create(new StringableData());
        $this->assertSame('hello', $stream->getContents());
    }

    public function testCreateWhenInvalidObject()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        (new StreamFactory())->create(new \stdClass());
    }

    public function testCreateWhenResource()
    {
        $r = fopen('php://memory', 'r+');
        $stream = (new StreamFactory())->create($r);
        $this->assertSame($r, $stream->detach());
    }

    public function testCreateWhenInvalidType()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        (new StreamFactory())->create([]);
    }
}

class StringableData
{
    public function __toString()
    {
        return 'hello';
    }
}

class JsonData implements \JsonSerializable
{
    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        return ['msg' => 'hello world'];
    }
}
