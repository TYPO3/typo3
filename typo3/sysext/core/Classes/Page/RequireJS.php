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

namespace TYPO3\CMS\Core\Page;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class RequireJS implements \JsonSerializable
{
    protected string $uri;
    protected array $config;

    public static function create(string $uri, array $config): self
    {
        return GeneralUtility::makeInstance(self::class, $uri, $config);
    }

    /**
     * @param string $uri URI to load require.js implementation
     * @param array $config require.js initialization configuration
     */
    public function __construct(string $uri, array $config)
    {
        $this->uri = $uri;
        $this->config = $config;
    }

    public function jsonSerialize(): array
    {
        return [
            'uri' => $this->uri,
            'config' => $this->config,
        ];
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
