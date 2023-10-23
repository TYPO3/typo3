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

namespace TYPO3\CMS\Install\Updates;

use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Registry for upgrade wizards. The registry receives all services, tagged with "install.upgradewizard".
 * The tagging of upgrade wizards is automatically done based on the PHP Attribute UpgradeWizard.
 *
 * @internal
 */
class UpgradeWizardRegistry
{
    public function __construct(
        private readonly ServiceLocator $upgradeWizards
    ) {}

    /**
     * Whether a registered upgrade wizard exists for the given identifier
     */
    public function hasUpgradeWizard(string $identifier): bool
    {
        return $this->upgradeWizards->has($identifier);
    }

    /**
     * Get registered upgrade wizard by identifier
     */
    public function getUpgradeWizard(string $identifier): UpgradeWizardInterface
    {
        if (!$this->hasUpgradeWizard($identifier)) {
            throw new \UnexpectedValueException('Upgrade wizard with identifier ' . $identifier . ' is not registered.', 1673964964);
        }

        return $this->upgradeWizards->get($identifier);
    }

    /**
     * Get all registered upgrade wizards
     *
     * @return array
     */
    public function getUpgradeWizards(): array
    {
        return $this->upgradeWizards->getProvidedServices();
    }
}
