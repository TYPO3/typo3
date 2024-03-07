<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Message;

use Psr\Http\Message\ResponseInterface;

interface ResourceResponseInterface extends ResponseInterface
{

    public const PHP_OBJECT_CONTENT_TYPE = "application/x-php-object";

    public function getBodyObject(): null|iterable|object;

    public function withBodyObject(null|iterable|object $bodyObject): ResponseInterface;

}
