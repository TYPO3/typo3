<?php
namespace TYPO3\CMS\SysNote\Core;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Bootstrap for note module
 */
class Bootstrap
{
    /**
     * Do not touch if you are not sure what you are doing!
     * @var array
     */
    protected $extbaseConfiguration = [
        'vendorName' => 'TYPO3\\CMS',
        'extensionName' => 'SysNote',
        'pluginName' => 'Note',
    ];

    /**
     * @var array
     */
    protected $currentGetArguments;

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @param DatabaseConnection $databaseConnection
     */
    public function __construct(DatabaseConnection $databaseConnection = null)
    {
        $this->databaseConnection = $databaseConnection ?: $GLOBALS['TYPO3_DB'];
    }

    /**
     * Bootstrap extbase and execute controller
     *
     * @param string $controllerName Controller to execute
     * @param string $actionName Action to run
     * @param array $arguments Arguments to pass to the controller action
     * @return string
     */
    public function run($controllerName, $actionName, array $arguments = [])
    {
        if (!$this->expectOutput($arguments)) {
            return '';
        }
        $arguments['controller'] = ucfirst(trim($controllerName));
        $arguments['action'] = lcfirst(trim($actionName));
        $this->overrideGetArguments($arguments);
        /** @var $extbaseBootstrap \TYPO3\CMS\Extbase\Core\Bootstrap */
        $extbaseBootstrap = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Core\Bootstrap::class);
        $content = $extbaseBootstrap->run('', $this->extbaseConfiguration);
        $this->revertGetArguments();
        return $content;
    }

    /**
     * Check if the note plugin expects output. If there are no sys_note records on the given
     * pages, the extbase bootstrap doesn't have to run the complete plugin.
     * This mechanism should increase the performance of the hooked backend modules heavily.
     *
     * @param array $arguments Arguments for the extbase plugin
     * @return bool
     */
    protected function expectOutput(array $arguments = [])
    {
        // no pids set
        if (!isset($arguments['pids']) || empty($arguments['pids']) || empty($GLOBALS['BE_USER']->user['uid'])) {
            return false;
        }
        $pidList = $this->databaseConnection->cleanIntList($arguments['pids']);
        if (empty($pidList)) {
            return false;
        }
        // check if there are records
        return $this->databaseConnection->exec_SELECTcountRows('*', 'sys_note', 'pid IN (' . $pidList . ')' . BackendUtility::deleteClause('sys_note')) > 0;
    }

    /**
     * Modify $_GET to force specific controller, action and arguments in
     * extbase bootstrap process
     *
     * Note: Overwriting $_GET was the most simple solution here until extbase
     * provides a clean way to force a controller and action in backend mode.
     *
     * @param array $arguments The arguments to set
     * @return void
     */
    protected function overrideGetArguments(array $arguments)
    {
        $this->currentGetArguments = $_GET;
        $_GET['tx_sysnote_note'] = $arguments;
    }

    /**
     * Revert previously backuped get arguments
     *
     * @return void
     */
    protected function revertGetArguments()
    {
        if (is_array($this->currentGetArguments)) {
            $_GET = $this->currentGetArguments;
        }
    }
}
