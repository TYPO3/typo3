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

/**
 * To be implemented by all configuration providers
 */
interface ProviderInterface
{
    /**
     * This method must exists since it's called from the provider
     * registry to provide the tag attributes from the definition.
     *
     * Note: We use __invoke so provider implementations are still
     * able to use dependency injection via constructor arguments.
     *
     * @param array $attributes
     * @return $this
     */
    public function __invoke(array $attributes): self;

    /**
     * Returns the provider identifier
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Returns the providers' label (locallang or static text)
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Returns the configuration, displayed in the module
     *
     * @return array
     */
    public function getConfiguration(): array;
}
