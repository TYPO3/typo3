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
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourceUriGeneratorInterface;

/**
 * This interface is public API and can be referenced in third party code
 * or throughout the TYPO3 core.
 * Implementations of this interface are internal though
 * and must only happen in TYPO3\CMS\Core\SystemResource namespace
 */
interface PublicResourceInterface extends StaticResourceInterface
{
    /**
     * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
     */
    public function getPublicUri(SystemResourceUriGeneratorInterface $uriGenerator): UriInterface;

    /**
     * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
     */
    public function isPublished(): bool;
}
