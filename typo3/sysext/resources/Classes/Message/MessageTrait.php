<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Resources\Validation\NameValidator;
use Webmozart\Assert\Assert;

trait MessageTrait
{

    protected readonly ProtocolVersion $protocolVersion;
    protected readonly array $headers;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion->value;
    }

    public function withProtocolVersion(ProtocolVersion|string $version): MessageInterface
    {
        $version = is_string($version) ? ProtocolVersion::from($version) : $version;
        return $this->mutate(['protocolVersion' => $version]);
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getHeader(string $name): array
    {
        if (!$this->hasHeader($name)) {
            return [];
        }
        $headerValue = $this->headers[$name];
        if (is_array($headerValue)) {
            return $headerValue;
        }
        return [$headerValue];
    }

    public function getHeaderLine(string $name): string
    {
        $headerValue = $this->getHeader($name);
        if (empty($headerValue)) {
            return '';
        }
        return implode(',', $headerValue);
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $value = is_string($value) ? [$value] : $value;

        Assert::allString($value);
        NameValidator::isQualifiedName($name);
        Assert::allRegex($value, '/^[:print:]$/');

        $headers = $this->getHeaders();
        $headers[$name] = $value;

        return $this->mutate(['headers' => $headers]);
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $value = is_string($value) ? [$value] : $value;

        Assert::allString($value);
        NameValidator::isQualifiedName($name);
        Assert::allRegex($value, '/^[:print:]$/');

        $headers = $this->getHeaders();
        $headers[$name] = \array_merge($headers[$name] ?? [], $value);

        return $this->mutate(['headers' => $headers]);
    }

    public function withoutHeader(string $name): MessageInterface
    {
        if (!$this->hasHeader($name)) {
            return clone $this;
        }

        $headers = $this->getHeaders();
        unset($headers[$name]);

        return $this->mutate(['headers' => $headers]);
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        return $this->mutate(['body' => $body]);
    }

}
