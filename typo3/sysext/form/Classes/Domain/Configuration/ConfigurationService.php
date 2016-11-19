<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Configuration;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;

/**
 * Helper for configuration settings
 *
 * Scope: frontend / backend
 */
class ConfigurationService
{

    /**
     * @var array
     */
    protected $formSettings;

    /**
     * @internal
     */
    public function initializeObject()
    {
        $this->formSettings = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ConfigurationManagerInterface::class)
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'form');
    }

     /**
      * Get the prototype configuration
      *
      * @param string $prototypeName name of the prototype to get the configuration for
      * @return array the prototype configuration
      * @throws PrototypeNotFoundException if prototype with the name $prototypeName was not found
      * @api
      */
     public function getPrototypeConfiguration(string $prototypeName): array
     {
         if (!isset($this->formSettings['prototypes'][$prototypeName])) {
             throw new PrototypeNotFoundException(sprintf('The Prototype "%s" was not found.', $prototypeName), 1475924277);
         }
         return $this->formSettings['prototypes'][$prototypeName];
     }
}
