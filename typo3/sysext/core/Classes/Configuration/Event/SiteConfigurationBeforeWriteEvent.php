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

namespace TYPO3\CMS\Core\Configuration\Event;

/**
 * Event fired before a site configuration is written to a yaml file
 * allows dynamic modification of the site's configuration before writing.
 */
final class SiteConfigurationBeforeWriteEvent
{
    public function __construct(
        protected string $siteIdentifier,
        protected array $configuration
    ) {}

    public function getSiteIdentifier(): string
    {
        return $this->siteIdentifier;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @param array $configuration overwrite the configuration array of the site
     */
    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}
