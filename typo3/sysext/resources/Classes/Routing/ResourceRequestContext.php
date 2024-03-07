<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Routing;

use TYPO3\CMS\Resources\Message\ResourceRequestInterface;

class ResourceRequestContext extends \Symfony\Component\Routing\RequestContext
{

    public const HOST_UNDEFINED = '/';
    public const SCHEME_TYPO3 = 't3';

    public function __construct(string $method = 'GET', string $path = '/', string $queryString = '')
    {
        parent::setHost(self::HOST_UNDEFINED);
        parent::setMethod($method);
        parent::setScheme(self::SCHEME_TYPO3);
        parent::setPathInfo($path);
        parent::setQueryString($queryString);
    }

    public function setScheme(string $scheme): static
    {
        throw new \BadMethodCallException('scheme is read-only', 1709733648);
    }

    public function setHttpPort(int $httpPort): static
    {
        throw new \BadMethodCallException('HTTP port is read-only', 1709733664);
    }

    public function setHttpsPort(int $httpsPort): static
    {
        throw new \BadMethodCallException('HTTPS port is read-only', 1709733669);
    }

    public static function fromResourceRequest(ResourceRequestInterface $resourceRequest)
    {
        return new static(
            $resourceRequest->getMethod(),
            $resourceRequest->getUri()->getPath(),
            $resourceRequest->getUri()->getQuery(),
        );
    }
}
