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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;

/**
 * This class is a wrapper for WebInfo controller of belog.
 * It is registered in ext_tables.php with \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction()
 * and called by the info extension via SCbase functionality.
 *
 * Extbase currently provides no way to register a "TBE_MODULES_EXT" module directly,
 * therefore we need to bootstrap extbase on our own here to jump to the WebInfo controller.
 * @internal This class is a experimental and is not considered part of the Public TYPO3 API.
 */
class BackendLogModuleBootstrap
{
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
        $_GET['tx_belog_system_beloglog']['controller'] = 'BackendLog';
        $_GET['tx_belog_system_beloglog']['pageId'] = GeneralUtility::_GP('id');
        $_GET['tx_belog_system_beloglog']['layout'] = 'Plain';
        $extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        return $extbaseBootstrap->run('', $configuration);
    }
}
