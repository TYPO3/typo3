<?php
namespace TYPO3\CMS\Beuser\Service;

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

/**
 * Module data storage service.
 * Used to store and retrieve module state (eg. checkboxes, selections).
 */
class ModuleDataStorageService implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var string
     */
    const KEY = 'tx_beuser';

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Loads module data for user settings or returns a fresh object initially
     *
     * @return \TYPO3\CMS\Beuser\Domain\Model\ModuleData
     */
    public function loadModuleData()
    {
        $moduleData = $GLOBALS['BE_USER']->getModuleData(self::KEY);
        if (empty($moduleData) || !$moduleData) {
            $moduleData = $this->objectManager->get(\TYPO3\CMS\Beuser\Domain\Model\ModuleData::class);
        } else {
            $moduleData = @unserialize($moduleData);
        }
        return $moduleData;
    }

    /**
     * Persists serialized module data to user settings
     *
     * @param \TYPO3\CMS\Beuser\Domain\Model\ModuleData $moduleData
     * @return void
     */
    public function persistModuleData(\TYPO3\CMS\Beuser\Domain\Model\ModuleData $moduleData)
    {
        $GLOBALS['BE_USER']->pushModuleData(self::KEY, serialize($moduleData));
    }
}
