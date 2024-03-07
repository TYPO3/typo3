<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Message;

interface MessageAttributesInterface
{

    /**
     * @see ServerRequestInterface::getAttributes()
     */
    public function getAttributes(): array;

    /**
     * @see ServerRequestInterface::getAttribute()
     */
    public function getAttribute(string $name, $default = null);

    /**
     * @see ServerRequestInterface::withAttribute()
     */
    public function withAttribute(string $name, $value): MessageAttributesInterface;

    /**
     * @see ServerRequestInterface::withoutAttribute()
     */
    public function withoutAttribute(string $name): MessageAttributesInterface;

}
