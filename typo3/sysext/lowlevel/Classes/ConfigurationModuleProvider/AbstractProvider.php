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

namespace TYPO3\CMS\Lowlevel\ConfigurationModuleProvider;

use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Can be used by specific provider implementations and supports
 * basic functionality, required by the interface.
 */
abstract class AbstractProvider implements ProviderInterface
{
    protected string $identifier;
    protected string $label;

    public function __invoke(array $attributes): self
    {
        if (!($attributes['label'] ?? false)) {
            throw new \RuntimeException('Attribute \'label\' must be set to use ' . __CLASS__, 1606478090);
        }

        $this->identifier = $attributes['identifier'];
        $this->label = $attributes['label'];
        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLabel(): string
    {
        return $this->getLanguageService()->sL($this->label);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
