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
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    ) {
    }

    /**
     * Whether a registered upgrade wizard exists for the given identifier
     */
    public function hasUpgradeWizard(string $identifier): bool
    {
        return $this->upgradeWizards->has($identifier) || $this->getLegacyUpgradeWizardClassName($identifier) !== null;
    }

    /**
     * Get registered upgrade wizard by identifier
     */
    public function getUpgradeWizard(string $identifier): UpgradeWizardInterface
    {
        if (!$this->hasUpgradeWizard($identifier)) {
            throw new \UnexpectedValueException('Upgrade wizard with identifier ' . $identifier . ' is not registered.', 1673964964);
        }

        return $this->getLegacyUpgradeWizard($identifier) ?? $this->upgradeWizards->get($identifier);
    }

    /**
     * Get all registered upgrade wizards
     *
     * @return array
     */
    public function getUpgradeWizards(): array
    {
        return array_replace(
            $this->upgradeWizards->getProvidedServices(),
            $this->getLegacyUpgradeWizards()
        );
    }

    /**
     * @deprecated Remove with TYPO3 v13
     */
    private function getLegacyUpgradeWizardClassName(string $identifier): ?string
    {
        // will only return true for UpgradeWizardInterface implementations, but not for RowUpdaterInterface
        // allowing RowUpdaters to fall back to a simplified handling.
        if (class_exists($identifier) && is_subclass_of($identifier, UpgradeWizardInterface::class)) {
            return $identifier;
        }
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier])
            && class_exists($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier])
        ) {
            return $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier];
        }
        return null;
    }

    /**
     * @deprecated Remove with TYPO3 v13
     */
    private function getLegacyUpgradeWizard(string $identifier): ?UpgradeWizardInterface
    {
        $className = $this->getLegacyUpgradeWizardClassName($identifier);
        if ($className === null) {
            return null;
        }

        $instance = GeneralUtility::makeInstance($className);
        return $instance instanceof UpgradeWizardInterface ? $instance : null;
    }

    /**
     * @deprecated Remove with TYPO3 v13
     */
    private function getLegacyUpgradeWizards(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] ?? [];
    }
}
