<?php

namespace TYPO3\CMS\Resources\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

interface ResourceRequestInterface extends RequestInterface, MessageAttributesInterface
{

    public function getMethodObject(): Method;
}
