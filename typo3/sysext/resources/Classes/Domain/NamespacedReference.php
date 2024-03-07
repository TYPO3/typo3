<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Domain;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Resources\Validation\NameValidator;
use Webmozart\Assert\Assert;

final readonly class NamespacedReference implements NamespacedReferenceInterface
{

    public function __construct(
        private Reference $namespace,
        private string    $type,
        private string    $identifier
    )
    {
        NameValidator::isDNS1123Subdomain($this->type);
        NameValidator::isValidLabelValue($this->identifier);
    }

    public function getNamespace(): ReferenceInterface
    {
        return $this->namespace;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function toUri(): UriInterface
    {
        return (new ResourceUri())
            ->withScheme('t3')
            ->withHost($this->namespace->getType())
            ->withPath(implode('/', [$this->namespace->getIdentifier(), $this->type, $this->identifier]));
    }

    public static function fromUri(UriInterface $uri): ReferenceInterface
    {
        Assert::eq($uri->getScheme(), 't3');
        $pathParts = explode('/', $uri->getPath());
        Assert::count($pathParts, 4);
        return new self(new Reference($uri->getHost(), $pathParts[1]), $pathParts[2], $pathParts[3]);
    }

}
