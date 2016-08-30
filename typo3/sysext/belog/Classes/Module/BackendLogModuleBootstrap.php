<?php
namespace TYPO3\CMS\Belog\Module;

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
 * This class is a wrapper for WebInfo controller of belog.
 * It is registered in ext_tables.php with \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction()
 * and called by the info extension via SCbase functionality.
 *
 * Extbase currently provides no way to register a "TBE_MODULES_EXT" module directly,
 * therefore we need to bootstrap extbase on our own here to jump to the WebInfo controller.
 */
class BackendLogModuleBootstrap
{
    /**
     * Dummy method, called by SCbase external object handling
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Dummy method, called by SCbase external object handling
     *
     * @return void
     */
    public function checkExtObj()
    {
    }

    /**
     * Bootstrap extbase and jump to WebInfo controller
     *
     * @return string
     */
    public function main()
    {
        $configuration = [
            'extensionName' => 'Belog',
            'pluginName' => 'system_BelogLog',
            'vendorName' => 'TYPO3\\CMS',
        ];
        // Yeah, this is ugly. But currently, there is no other direct way
        // in extbase to force a specific controller in backend mode.
        // Overwriting $_GET was the most simple solution here until extbase
        // provides a clean way to solve this.
        $_GET['tx_belog_system_beloglog']['controller'] = 'WebInfo';
        /** @var $extbaseBootstrap \TYPO3\CMS\Extbase\Core\Bootstrap */
        $extbaseBootstrap = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Core\Bootstrap::class);
        return $extbaseBootstrap->run('', $configuration);
    }
}
