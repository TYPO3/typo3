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

namespace TYPO3\CMS\Lowlevel\Event;

/**
 * Listeners to this Event will be able to modify the blinded configuration
 * options, displayed in the configuration module of the TYPO3 backend.
 */
final class ModifyBlindedConfigurationOptionsEvent
{
    public function __construct(private array $blindedConfigurationOptions)
    {
    }

    public function setBlindedConfigurationOptions(array $blindedConfigurationOptions): void
    {
        $this->blindedConfigurationOptions = $blindedConfigurationOptions;
    }

    public function getBlindedConfigurationOptions(): array
    {
        return $this->blindedConfigurationOptions;
    }
}
