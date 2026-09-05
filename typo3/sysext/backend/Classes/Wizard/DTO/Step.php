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

namespace TYPO3\CMS\Backend\Wizard\DTO;

/**
 * DTO for a single wizard step with module and configuration data.
 * Used in WizardProviderInterface::getConfiguration() when dynamically loading wizard steps.
 *
 * @internal
 */
final class Step implements \JsonSerializable
{
    private array $configurationData = [];

    private function __construct(private readonly string $module) {}

    public function jsonSerialize(): mixed
    {
        return [
            'module' => $this->module,
            'configurationData' => $this->configurationData,
        ];
    }

    public static function create(string $module): self
    {
        return new self($module);
    }

    public function withConfigurationData(array $configurationData): self
    {
        return clone($this, ['configurationData' => $configurationData]);
    }
}
