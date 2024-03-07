<?php

namespace TYPO3\CMS\Resources\Domain;

use Psr\Http\Message\UriInterface;

interface ReferenceInterface
{
    public function getType(): string;

    public function getIdentifier(): string;

    public function toUri(): UriInterface;

    public static function fromUri(UriInterface $uri): self;
}
