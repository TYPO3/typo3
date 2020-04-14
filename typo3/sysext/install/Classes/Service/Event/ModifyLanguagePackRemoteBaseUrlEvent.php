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

namespace TYPO3\CMS\Install\Service\Event;

use Psr\Http\Message\UriInterface;

/**
 * Event to modify the main URL of a language
 */
final class ModifyLanguagePackRemoteBaseUrlEvent
{
    /**
     * @var UriInterface
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $packageKey;

    public function __construct(UriInterface $baseUrl, string $packageKey)
    {
        $this->baseUrl = $baseUrl;
        $this->packageKey = $packageKey;
    }

    public function getBaseUrl(): UriInterface
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(UriInterface $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function getPackageKey(): string
    {
        return $this->packageKey;
    }
}
