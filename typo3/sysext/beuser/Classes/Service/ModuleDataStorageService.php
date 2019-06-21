<?php
namespace TYPO3\CMS\Beuser\Service;

use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Beuser\Domain\Model\ModuleData;

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
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
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
     * Loads module data for user settings or returns a fresh object if module data is invalid or unset
     *
     * @return \TYPO3\CMS\Beuser\Domain\Model\ModuleData
     */
    public function loadModuleData()
    {
        $moduleData = $GLOBALS['BE_USER']->getModuleData(self::KEY) ?? '';
        if ($moduleData !== '') {
            $moduleData = @unserialize($moduleData, ['allowed_classes' => [ModuleData::class, Demand::class]]);
            if ($moduleData instanceof ModuleData) {
                return $moduleData;
            }
        }

        return $this->objectManager->get(ModuleData::class);
    }

    /**
     * Persists serialized module data to user settings
     *
     * @param \TYPO3\CMS\Beuser\Domain\Model\ModuleData $moduleData
     */
    public function persistModuleData(\TYPO3\CMS\Beuser\Domain\Model\ModuleData $moduleData)
    {
        $GLOBALS['BE_USER']->pushModuleData(self::KEY, serialize($moduleData));
    }
}
