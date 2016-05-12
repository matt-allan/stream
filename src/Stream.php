<?php

namespace Yuloh\Stream;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    private $readable = [
        'r',
        'w+',
        'r+',
        'x+',
        'c+',
        'rb',
        'w+b',
        'r+b',
        'x+b',
        'c+b',
        'rt',
        'w+t',
        'r+t',
        'x+t',
        'c+t',
        'a+',
    ];

    private $writable = [
        'w',
        'w+',
        'rw',
        'r+',
        'x+',
        'c+',
        'wb',
        'w+b',
        'r+b',
        'x+b',
        'c+b',
        'w+t',
        'r+t',
        'x+t',
        'c+t',
        'a',
        'a+',
    ];

    /**
     * @var resource
     */
    private $resource;

    /**
     * @param resource $resource
     * @throws \InvalidArgumentException
     */
    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException(
                sprintf('Expected a valid resource, got "%s".', gettype($resource))
            );
        }

        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (\RuntimeException $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->resource) {
            fclose($this->detach());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $resource       = $this->resource;
        $this->resource = null;

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        if (!$this->resource) {
            return null;
        }

        return fstat($this->resource)['size'];
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        if (!$this->resource) {
            throw new \RuntimeException('Cannot find the current pointer position, the stream is detached.');
        }

        if (($pos = ftell($this->resource)) !== false) {
            return $pos;
        }

        throw new \RuntimeException('Unable to find current pointer position of stream.');
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        if (!$this->resource) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return $this->resource && $this->getMetadata('seekable');
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        $this->throwIfUnSeekable();

        if (fseek($this->resource, $offset, $whence) === 0) {
            return;
        }

        throw new \RuntimeException('Unable to seek stream.');
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return $this->resource && in_array($this->getMetadata('mode'), $this->writable);
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        $this->throwIfUnwritable();

        if ($bytesWritten = fwrite($this->resource, $string)) {
            return $bytesWritten;
        }

        throw new \RuntimeException('Unable to write stream.');
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return $this->resource && in_array($this->getMetadata('mode'), $this->readable);
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        $this->throwIfUnreadable();

        if ($contents = fread($this->resource, $length)) {
            return $contents;
        }

        throw new \RuntimeException('Unable to read stream.');
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        $this->throwIfUnreadable();

        if (($contents = stream_get_contents($this->resource)) !== false) {
            return $contents;
        }

        throw new \RuntimeException('Unable to read stream.');
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if (!$this->resource) {
            return $key ? null : [];
        }

        $metadata = stream_get_meta_data($this->resource);

        if (is_null($key)) {
            return $metadata;
        }

        return array_key_exists($key, $metadata) ? $metadata[$key] : null;
    }

    /**
     * @throws \RuntimeException
     */
    private function throwIfUnwritable()
    {
        if (!$this->isWritable()) {
            throw new \RuntimeException('The stream is not writable.');
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function throwIfUnreadable()
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException('The stream is not readable.');
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function throwIfUnSeekable()
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException('The stream is not seekable.');
        }
    }
}
