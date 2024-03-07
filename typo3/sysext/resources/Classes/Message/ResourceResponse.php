<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\Stream;

class ResourceResponse implements ResourceResponseInterface
{

    use MutationTrait, MessageTrait, AttributesTrait;

    protected readonly array $headers;
    protected readonly StreamInterface $body;

    public function __construct(
        protected readonly Status $status,
        protected readonly null|iterable|object $bodyObject = null,
        array $headers = [],
        protected readonly array $attributes = [],
        protected readonly ProtocolVersion $protocolVersion = ProtocolVersion::v1alpha
    )
    {
        $headers['Content-Type'] = $headers['Content-Type'] ?? ResourceResponseInterface::PHP_OBJECT_CONTENT_TYPE;
        $this->headers = $headers;
        $this->body = new Stream('php://temp', 'r+');
    }

    public function getStatusCode(): int
    {
        return $this->status->value;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->mutate(['status' => Status::from($code)]);
    }

    public function getReasonPhrase(): string
    {
        return $this->status->getReasonPhrase();
    }

    /**
     * @TODO body should be an object stream
     */
    public function getBodyObject(): null|iterable|object
    {
        return $this->bodyObject;
    }

    public function withBodyObject(null|iterable|object $bodyObject): ResponseInterface
    {
        return $this->mutate(['bodyObject' => $bodyObject]);
    }
}
