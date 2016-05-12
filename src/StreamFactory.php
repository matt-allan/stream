<?php

namespace Yuloh\Stream;

class StreamFactory extends AbstractStreamFactory
{
    /**
     * @param resource $resource
     *
     * @return \Yuloh\Stream\Stream
     */
    protected function forResource($resource)
    {
        return new Stream($resource);
    }
}
