<?php

namespace QBF\BackupPilot\Http\Client;

/**
 * Exception for when a request failed.
 */
interface RequestExceptionInterface extends ClientExceptionInterface
{
    /**
     * Returns the request.
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getRequest();
}
