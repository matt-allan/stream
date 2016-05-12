<?php

namespace Yuloh\Stream;

interface StreamFactoryInterface
{
    /**
     * Create a new stream.
     *
     * @param null|integer|double|string|object|resource $resource
     *
     * @return \Psr\Http\Message\StreamInterface
     */
    public function create($resource = null);
}
