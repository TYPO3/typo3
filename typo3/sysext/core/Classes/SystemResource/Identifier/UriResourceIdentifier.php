<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\SystemResource\Identifier;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;

/**
 * This is subject to change during v14 development. Do not use.
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
final class UriResourceIdentifier extends SystemResourceIdentifier
{
    public const TYPE = 'URI';
    private readonly UriInterface $uri;

    public function __construct(string $givenIdentifier)
    {
        parent::__construct($givenIdentifier);
        if (str_starts_with($givenIdentifier, self::TYPE)) {
            $uri = substr($givenIdentifier, strlen(self::TYPE) + 1);
        }
        $this->uri = new Uri($uri ?? $givenIdentifier);
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function __toString()
    {
        if ($this->isRelative()) {
            return $this->givenIdentifier;
        }
        return (string)$this->uri;
    }

    private function isRelative(): bool
    {
        return $this->uri->getAuthority() === '';
    }
}
