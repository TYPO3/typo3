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

namespace TYPO3\CMS\Lowlevel\Tests\Functional\Command\Fixtures;

use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderInterface;

final class InspectFixtureProvider implements ProviderInterface
{
    private string $identifier = '';
    private string $label = '';

    public function __construct(
        private readonly array|\Throwable $configuration,
    ) {}

    public function __invoke(array $attributes): self
    {
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
        return $this->label;
    }

    public function getConfiguration(): array
    {
        if ($this->configuration instanceof \Throwable) {
            throw $this->configuration;
        }
        return $this->configuration;
    }
}
