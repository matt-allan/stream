<?php

namespace Yuloh\Stream\Adapters;

use Yuloh\Stream\AbstractStreamFactory;
use Zend\Diactoros\Stream;

class DiactorosStreamFactory extends AbstractStreamFactory
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
