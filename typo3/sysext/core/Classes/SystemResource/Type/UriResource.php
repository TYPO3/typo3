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

namespace TYPO3\CMS\Core\SystemResource\Type;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourceUriGeneratorInterface;

/**
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
final class UriResource extends Uri implements StaticResourceInterface, PublicResourceInterface
{
    public function getPublicUri(SystemResourceUriGeneratorInterface $uriGenerator): UriInterface
    {
        return $this;
    }

    public function isPublished(): bool
    {
        return true;
    }

    public function getResourceIdentifier(): string
    {
        return (string)$this;
    }
}
