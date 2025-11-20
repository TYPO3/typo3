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

namespace TYPO3\CMS\Core\SystemResource\Publishing\FileSystem;

use TYPO3\CMS\Core\Core\Environment;

/**
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
final readonly class PublishingConfiguration
{
    private string $publishingType;

    public function __construct(?string $publishingType = null)
    {
        $publishingType = $publishingType ?? $GLOBALS['TYPO3_CONF_VARS']['SYS']['SystemResources']['filesystemPublishingType'] ?? 'auto';
        if ($publishingType === 'auto') {
            $publishingType = Environment::getContext()->isDevelopment() ? 'link' : 'mirror';
        }
        $this->publishingType = $publishingType;
    }

    public function isLinkPublishingEnabled(): bool
    {
        return $this->publishingType === 'link';
    }

    public function isMirrorPublishingEnabled(): bool
    {
        return $this->publishingType === 'mirror';
    }

    public function hasCustomPublishingType(): bool
    {
        return !$this->isMirrorPublishingEnabled() && !$this->isLinkPublishingEnabled();
    }

    public function getCustomPublishingType(): string
    {
        if (!$this->hasCustomPublishingType()) {
            throw new \LogicException('There is no custom publishing type enabled', 1773138713);
        }
        return $this->publishingType;
    }
}
