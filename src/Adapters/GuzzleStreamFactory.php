<?php

namespace Yuloh\Stream\Adapters;

use Yuloh\Stream\AbstractStreamFactory;
use GuzzleHttp\Psr7\Stream;

class GuzzleStreamFactory extends AbstractStreamFactory
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
