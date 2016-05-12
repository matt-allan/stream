<?php

namespace Yuloh\Stream\Adapters;

use Yuloh\Stream\AbstractStreamFactory;
use Slim\Http\Stream;

class SlimStreamFactory extends AbstractStreamFactory
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
