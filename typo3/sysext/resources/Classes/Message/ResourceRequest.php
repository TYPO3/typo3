<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Resources\Domain\ResourceUri;
use Webmozart\Assert\Assert;

class ResourceRequest implements ResourceRequestInterface
{

    use MutationTrait, MessageTrait, AttributesTrait;

    public function __construct(
        protected readonly ResourceUri $uri,
        protected readonly Method $method = Method::Read,
        protected readonly StreamInterface $body = new Stream('php://temp', 'r+'),
        protected readonly array $headers = [],
        protected readonly array $attributes = [],
        protected readonly ProtocolVersion $protocolVersion = ProtocolVersion::v1alpha
    )
    {}

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        return $this->mutate(['uri' => $uri]);
    }

    public function getRequestTarget(): string
    {
        $requestTarget = '';
        if ($host = $this->uri->getHost()) {
            $requestTarget .= '/' . $host;
        }
        if ($path = $this->uri->getPath()) {
            $requestTarget .= $path;
        }
        if ($query = $this->uri->getQuery()) {
            $requestTarget .= '?' . $query;
        }
        return '/' . ltrim($requestTarget, '/');
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        Assert::regex($requestTarget, '/^\S+$/');

        $uri = new ResourceUri($requestTarget);

        return $this->mutate(['uri' => $uri]);
    }

    public function getMethod(): string
    {
        return $this->method->value;
    }

    public function withMethod(Method|string $method): RequestInterface
    {
        $method = is_string($method) ? Method::from($method) : $method;
        return $this->mutate(['method' => $method]);
    }

    public function getMethodObject(): Method
    {
        return $this->method;
    }
}
