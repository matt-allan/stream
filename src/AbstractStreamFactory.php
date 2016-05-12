<?php

namespace Yuloh\Stream;

abstract class AbstractStreamFactory implements StreamFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($resource = null)
    {
        switch (gettype($resource)) {
            case 'NULL':
                return $this->forNull();
            case 'integer':
            case 'double':
            case 'string':
                return $this->forString($resource);
            case 'object':
                return $this->forObject($resource);
            case 'resource':
                return $this->forResource($resource);
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'Unable to create stream for type "%s"',
                        gettype($resource)
                    )
                );
        }
    }

    /**
     * @param resource $resource
     *
     * @return \Yuloh\Stream\Stream
     */
    abstract protected function forResource($resource);

    /**
     * @param object $object
     *
     * @return \Yuloh\Stream\Stream
     */
    protected function forObject($object)
    {
        if (method_exists($object, '__toString')) {
            return $this->forString((string) $object);
        }

        if ($object instanceof \JsonSerializable) {
            return $this->forString(json_encode($object));
        }

        throw new \InvalidArgumentException(sprintf('Unable to create stream for "%s"', get_class($object)));
    }

    /**
     * @param string $string
     *
     * @return \Yuloh\Stream\Stream
     */
    protected function forString($string)
    {
        $fp = fopen('php://temp', 'r+');
        if ($string !== '') {
            fwrite($fp, $string);
            rewind($fp);
        }
        return $this->forResource($fp);
    }

    /**
     * @return \Yuloh\Stream\Stream
     */
    protected function forNull()
    {
        return $this->forResource(fopen('php://temp', 'r+'));
    }
}
