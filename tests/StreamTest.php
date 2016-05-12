<?php

namespace Yuloh\Stream\Tests;

use phpmock\Mock;
use phpmock\MockBuilder;
use Yuloh\Stream\Stream;

class StreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Mock[]
     */
    private $mocks = [];

    public function testConstructThrowsForInvalidArg()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        new Stream('hello world!');
    }

    public function testToString()
    {
        $r = fopen('php://memory', 'r+');
        fwrite($r, 'hello world!');
        $stream = new Stream($r);

        $this->assertSame('hello world!', $stream->__toString());
    }

    public function testToStringReturnsEmptyStringInsteadOfThrowing()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'yul');
        $r      = fopen($tmp, 'w');
        $stream = new Stream($r);
        $this->assertSame('', $stream->__toString());
        unlink($tmp);
    }

    public function testClose()
    {
        $r      = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $stream->close();
        $this->assertFalse(is_resource($r));
        // You should be able to call close again without an error.
        $stream->close();
        $this->assertFalse(is_resource($r));
    }

    public function testDetach()
    {
        $r        = fopen('php://memory', 'r+');
        $stream   = new Stream($r);
        $detached = $stream->detach();
        $this->assertSame($r, $detached, 'The resource should be returned.');
        $this->assertTrue(is_resource($detached), 'The resource should not be closed.');
        $this->assertFalse($stream->isReadable(), 'The stream should be unusable.');
    }

    public function testGetSize()
    {
        $r = fopen('php://memory', 'r+');
        fwrite($r, 'hello world!');
        $stream = new Stream($r);
        $this->assertSame(12, $stream->getSize());
    }

    public function testGetSizeReturnsNullWhenDetached()
    {
        $r = fopen('php://memory', 'r+');
        fwrite($r, 'hello world!');
        $stream = new Stream($r);
        $stream->detach();
        $this->assertNull($stream->getSize());
    }

    public function testTell()
    {
        $r = fopen('php://memory', 'r+');
        fwrite($r, 'hello world!');
        $stream = new Stream($r);
        $this->assertSame(12, $stream->tell(), 'The pointer should not be rewound when instantiating.');
        $stream->rewind();
        $this->assertSame(0, $stream->tell(), 'The current pointer position should be returned.');
    }

    public function testTellThrowsWhenDetached()
    {
        $this->setExpectedException(\RuntimeException::class);
        $r      = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $stream->detach();
        $stream->tell();
    }

    /**
     * @runInSeparateProcess
     */
    public function testTellThrowsWhenFailureOccurs()
    {
        $this->setExpectedException(\RuntimeException::class);
        $this->mockBuiltInFunction('ftell', function ($resource) {
            return false;
        });
        $r      = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $stream->tell();
    }

    public function testEofReturnsTrueWhenDetached()
    {
        $r = fopen('php://memory', 'r+');
        fwrite($r, 'hello world!');
        $stream = new Stream($r);
        $stream->rewind();
        $stream->detach();
        $this->assertTrue($stream->eof());
    }

    public function testEofReturnsTrueWhenAtEof()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'yul');
        file_put_contents($tmp, 'hello world!');
        $r      = fopen($tmp, 'r+');
        $stream = new Stream($r);
        fread($r, 4096);
        $this->assertTrue($stream->eof());
    }

    public function testEofReturnsFalseWhenNotAtEof()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'yul');
        file_put_contents($tmp, 'hello world!');
        $r      = fopen($tmp, 'r+');
        $stream = new Stream($r);
        $this->assertFalse($stream->eof());
        unlink($tmp);
    }

    public function testIsSeekable()
    {
        $r      = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $this->assertTrue($stream->isSeekable());
    }

    public function testIsSeekableReturnsFalseWhenDetached()
    {
        $r      = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $stream->detach();
        $this->assertFalse($stream->isSeekable());
    }

    public function testSeek()
    {
        $r = fopen('php://memory', 'r+');
        fwrite($r, 'hello world!');
        rewind($r);
        $stream = new Stream($r);
        $stream->seek(6);
        $this->assertSame(6, $stream->tell());
    }

    public function testSeekThrowsWhenDetached()
    {
        $this->setExpectedException(\RuntimeException::class);
        $r      = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $stream->detach();
        $stream->seek(12);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSeekThrowsWhenErrorOccurs()
    {
        $this->setExpectedException(\RuntimeException::class);
        $r      = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $this->mockBuiltInFunction('fseek', function () {
            return -1;
        });
        $stream->seek(12);
    }

    public function testRewind()
    {
        $r = fopen('php://memory', 'r+');
        fwrite($r, 'hello world!');
        $stream = new Stream($r);
        $stream->rewind();
        $this->assertSame(0, $stream->tell());
    }

    public function isWritableDataProvider()
    {
        return [
            ['w', true],
            ['w+', true],
            ['rw', true],
            ['r+', true],
            ['x+', true],
            ['c+', true],
            ['wb', true],
            ['w+b', true],
            ['r+b', true],
            ['x+b', true],
            ['c+b', true],
            ['w+t', true],
            ['r+t', true],
            ['x+t', true],
            ['c+t', true],
            ['a', true],
            ['a+', true],
            ['r', false],
            ['rb', false],
        ];
    }

    /**
     * @param string $mode
     * @param bool $isWritable
     * @dataProvider isWritableDataProvider
     */
    public function testIsWritable($mode, $isWritable)
    {
        // If it's a x* mode, don't create the file.
        if ($mode[0] === 'x') {
            $tmp = sys_get_temp_dir() . '/yuloh_stream_' . md5(time() . uniqid());
        } else {
            $tmp = tempnam(sys_get_temp_dir(), 'yul');
        }

        $r      = fopen($tmp, $mode);
        $stream = new Stream($r);
        if ($isWritable) {
            $this->assertTrue($stream->isWritable());
        } else {
            $this->assertFalse($stream->isWritable());
        }
        unlink($tmp);
    }

    public function testIsWritableReturnsFalseWhenDetached()
    {
        $r      = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $stream->detach();
        $this->assertFalse($stream->isWritable());
    }

    public function isReadableDataProvider()
    {
        return [
            ['r', true],
            ['w+', true],
            ['r+', true],
            ['x+', true],
            ['c+', true],
            ['rb', true],
            ['w+b', true],
            ['r+b', true],
            ['x+b', true],
            ['c+b', true],
            ['rt', true],
            ['w+t', true],
            ['r+t', true],
            ['x+t', true],
            ['c+t', true],
            ['a+', true],
            ['a', false],
            ['ab', false],
            ['c', false],
            ['cb', false],
            ['w', false],
            ['wb', false],
            ['x', false],
            ['xb', false],
        ];
    }

    /**
     * @param string $mode
     * @param bool $isReadable
     * @dataProvider isReadableDataProvider
     */
    public function testIsReadable($mode, $isReadable)
    {
        // If it's a x* mode, don't create the file.
        if ($mode[0] === 'x') {
            $tmp = sys_get_temp_dir() . '/yuloh_stream_' . md5(time() . uniqid());
        } else {
            $tmp = tempnam(sys_get_temp_dir(), 'yul');
        }

        $r      = fopen($tmp, $mode);
        $stream = new Stream($r);
        if ($isReadable) {
            $this->assertTrue($stream->isReadable());
        } else {
            $this->assertFalse($stream->isReadable());
        }
        unlink($tmp);
    }

    public function testIsReadableReturnsFalseWhenDetached()
    {
        $r      = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $stream->detach();
        $this->assertFalse($stream->isReadable());
    }

    public function testWrite()
    {
        $r      = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $bytesWritten = $stream->write('hello');
        $this->assertSame(5, $bytesWritten);
        $this->assertSame('hello', $stream->__toString());
    }

    /**
     * @runInSeparateProcess
     */
    public function testWriteThrowsIfErrorOccurs()
    {
        $this->setExpectedException(\RuntimeException::class);
        $this->mockBuiltInFunction('fwrite', function () {
            return false;
        });
        $r      = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $stream->write('hello');
    }

    public function testWriteThrowsIfUnwritable()
    {
        $this->setExpectedException(\RuntimeException::class);
        $r      = fopen('php://memory', 'r');
        $stream = new Stream($r);
        $stream->write('hello');
    }

    public function testRead()
    {
        $r = fopen('php://memory', 'r+');
        fwrite($r, 'hello world!');
        $stream = new Stream($r);
        $stream->rewind();
        $contents = $stream->read(5);
        $this->assertSame('hello', $contents);
    }

    public function testReadThrowsIfUnreadable()
    {
        $this->setExpectedException(\RuntimeException::class);
        $tmp = tempnam(sys_get_temp_dir(), 'yul');
        $r      = fopen($tmp, 'w');
        $stream = new Stream($r);
        $stream->read(5);
        unlink($tmp);
    }

    public function testReadThrowsIfErrorOccurs()
    {
        $this->setExpectedException(\RuntimeException::class);
        $r = fopen('php://memory', 'r+');
        fwrite($r, 'hello world!');
        $stream = new Stream($r);
        $contents = $stream->read(10);
    }

    public function testGetContents()
    {
        $r = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        fwrite($r, 'hello world!');
        $stream->seek(6);
        $this->assertSame('world!', $stream->getContents());
    }

    public function testGetContentsDoesNotThrowWhenEmpty()
    {
        $r = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $this->assertSame('', $stream->getContents());
    }

    public function testGetContentsThrowsIfUnreadable()
    {
        $this->setExpectedException(\RuntimeException::class);
        $tmp = tempnam(sys_get_temp_dir(), 'yul');
        $r      = fopen($tmp, 'w');
        $stream = new Stream($r);
        $stream->getContents();
        unlink($tmp);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetContentsThrowsIfErrorOccurs()
    {
        $this->setExpectedException(\RuntimeException::class);
        $this->mockBuiltInFunction('stream_get_contents', function () {
            return false;
        });
        $r = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $stream->getContents();
    }

    public function testGetMetadataReturnsArrayWithNoArgs()
    {
        $r = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $this->assertSame(stream_get_meta_data($r), $stream->getMetadata());
    }

    public function testGetMetadataReturnsSpecificDataWithKeySpecified()
    {
        $r = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $this->assertSame(stream_get_meta_data($r)['mode'], $stream->getMetadata('mode'));
    }

    public function testGetMetadataReturnsNullWhenKeyDoesNotExist()
    {
        $r = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $this->assertSame(null, $stream->getMetadata('undefined_key'));
    }

    public function testGetMetadataReturnsEmptyArrayWhenDetached()
    {
        $r = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $stream->detach();
        $this->assertSame([], $stream->getMetadata());
    }

    public function testGetMetadataReturnsNullWhenDetachedAndKeySpecified()
    {
        $r = fopen('php://memory', 'r+');
        $stream = new Stream($r);
        $stream->detach();
        $this->assertSame(null, $stream->getMetadata('mode'));
    }

    protected function mockBuiltInFunction($name, \Closure $callback)
    {
        $builder = new MockBuilder();
        $builder->setNamespace('Yuloh\Stream')
            ->setName($name)
            ->setFunction($callback);

        $mock = $builder->build();
        $mock->enable();
        return $mock;
    }

    protected function tearDown()
    {
        Mock::disableAll();
    }
}
