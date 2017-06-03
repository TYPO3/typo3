<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\Action;
use TYPO3\CMS\Install\FolderStructure\DefaultPermissionsCheck;

/**
 * "Environment" main controller
 */
class Environment extends Action\AbstractAction
{
    /**
     * Executes the tool
     *
     * @return string Rendered content
     */
    protected function executeAction(): string
    {
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $permissionCheck = GeneralUtility::makeInstance(DefaultPermissionsCheck::class);
        $this->view->assignMultiple([
            'folderStructureFilePermissionStatus' => $permissionCheck->getMaskStatus('fileCreateMask'),
            'folderStructureDirectoryPermissionStatus' => $permissionCheck->getMaskStatus('folderCreateMask'),

            'imageProcessingConfiguration' => $this->getImageConfiguration(),
            'imageProcessingToken' => $formProtection->generateToken('installTool', 'imageProcessing'),

            'mailTestToken' => $formProtection->generateToken('installTool', 'mailTest'),
            'mailTestSenderAddress' => $this->getSenderEmailAddress(),

            'systemInformationCgiDetected', GeneralUtility::isRunningOnCgiServerApi(),
            'systemInformationDatabaseConnections' => $this->getDatabaseConnectionInformation(),
            'systemInformationOperatingSystem' => TYPO3_OS === 'WIN' ? 'Windows' : 'Unix',
        ]);
        return $this->view->render();
    }

    /**
     * Gather image configuration overview
     *
     * @return array Result array
     */
    protected function getImageConfiguration(): array
    {
        return [
            'processor' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] === 'GraphicsMagick' ? 'GraphicsMagick' : 'ImageMagick',
            'processorEnabled' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'],
            'processorPath' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'],
            'processorVersion' => $this->determineImageMagickVersion(),
            'processorEffects' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_effects'],
            'gdlibEnabled' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'],
            'gdlibPng' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'],
            'fileFormats' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
        ];
    }

    /**
     * Determine ImageMagick / GraphicsMagick version
     *
     * @return string Version
     */
    protected function determineImageMagickVersion(): string
    {
        $command = CommandUtility::imageMagickCommand('identify', '-version');
        CommandUtility::exec($command, $result);
        $string = $result[0];
        list(, $version) = explode('Magick', $string);
        list($version) = explode(' ', trim($version));
        return trim($version);
    }

    /**
     * Get sender address from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
     * If this setting is empty fall back to 'no-reply@example.com'
     *
     * @return string Returns an email address
     */
    protected function getSenderEmailAddress(): string
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
            ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
            : 'no-reply@example.com';
    }

    /**
     * Get details about all configured database connections
     *
     * @return array
     */
    protected function getDatabaseConnectionInformation(): array
    {
        $connectionInfos = [];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        foreach ($connectionPool->getConnectionNames() as $connectionName) {
            $connection = $connectionPool->getConnectionByName($connectionName);
            $connectionParameters = $connection->getParams();
            $connectionInfo = [
                'connectionName' => $connectionName,
                'version' => $connection->getServerVersion(),
                'databaseName' => $connection->getDatabase(),
                'username' => $connection->getUsername(),
                'host' => $connection->getHost(),
                'port' => $connection->getPort(),
                'socket' => $connectionParameters['unix_socket'] ?? '',
                'numberOfTables' => count($connection->getSchemaManager()->listTableNames()),
                'numberOfMappedTables' => 0,
            ];
            if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'])
                && is_array($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'])
            ) {
                // Count number of array keys having $connectionName as value
                $connectionInfo['numberOfMappedTables'] = count(array_intersect(
                    $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'],
                    [$connectionName]
                ));
            }
            $connectionInfos[] = $connectionInfo;
        }
        return $connectionInfos;
    }
}
