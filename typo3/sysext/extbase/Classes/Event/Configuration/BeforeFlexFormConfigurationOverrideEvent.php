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

namespace TYPO3\CMS\Extbase\Event\Configuration;

/**
 * Event which is dispatched before flexForm configuration overrides framework configuration. Possible core flexForm
 * overrides have already been processed in `$flexFormConfiguration`.
 *
 * Listeners can implement a custom flexForm override process by using the original flexForm configuration available
 * in `$originalFlexFormConfiguration`.
 */
final class BeforeFlexFormConfigurationOverrideEvent
{
    public function __construct(
        protected readonly array $frameworkConfiguration,
        protected readonly array $originalFlexFormConfiguration,
        protected array $flexFormConfiguration
    ) {}

    public function getFrameworkConfiguration(): array
    {
        return $this->frameworkConfiguration;
    }

    public function getOriginalFlexFormConfiguration(): array
    {
        return $this->originalFlexFormConfiguration;
    }

    public function getFlexFormConfiguration(): array
    {
        return $this->flexFormConfiguration;
    }

    public function setFlexFormConfiguration(array $flexFormConfiguration): void
    {
        $this->flexFormConfiguration = $flexFormConfiguration;
    }
}
