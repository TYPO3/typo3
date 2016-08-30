<?php
namespace TYPO3\CMS\Install\Service;

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

/**
 * Expected schema service
 *
 * @internal use in install tool only!
 */
class SqlExpectedSchemaService
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager = null;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * Get expected schema array
     *
     * @return array Expected schema
     */
    public function getExpectedDatabaseSchema()
    {
        /** @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService $schemaMigrationService */
        $schemaMigrationService = $this->objectManager->get(\TYPO3\CMS\Install\Service\SqlSchemaMigrationService::class);
        // Raw concatenated ext_tables.sql and friends string
        $expectedSchemaString = $this->getTablesDefinitionString();
        // Remove comments
        $cleanedExpectedSchemaString = implode(LF, $schemaMigrationService->getStatementArray($expectedSchemaString, true, '^CREATE TABLE '));
        $expectedSchema = $schemaMigrationService->getFieldDefinitions_fileContent($cleanedExpectedSchemaString);
        return $expectedSchema;
    }

    /**
     * Cycle through all loaded extensions and get full table definitions as concatenated string
     *
     * @param bool $withStatic TRUE if sql from ext_tables_static+adt.sql should be loaded, too.
     * @return string Concatenated SQL of loaded extensions ext_tables.sql
     */
    public function getTablesDefinitionString($withStatic = false)
    {
        $sqlString = [];

        // Find all ext_tables.sql of loaded extensions
        $loadedExtensionInformation = $GLOBALS['TYPO3_LOADED_EXT'];
        foreach ($loadedExtensionInformation as $extensionConfiguration) {
            if ((is_array($extensionConfiguration) || $extensionConfiguration instanceof \ArrayAccess) && $extensionConfiguration['ext_tables.sql']) {
                $sqlString[] = GeneralUtility::getUrl($extensionConfiguration['ext_tables.sql']);
            }
            if ($withStatic
                && (is_array($extensionConfiguration) || $extensionConfiguration instanceof \ArrayAccess)
                && $extensionConfiguration['ext_tables_static+adt.sql']
            ) {
                $sqlString[] = GeneralUtility::getUrl($extensionConfiguration['ext_tables_static+adt.sql']);
            }
        }

        $sqlString = $this->emitTablesDefinitionIsBeingBuiltSignal($sqlString);

        return implode(LF . LF . LF . LF, $sqlString);
    }

    /**
     * Emits a signal to manipulate the tables definitions
     *
     * @param array $sqlString
     * @return mixed
     */
    protected function emitTablesDefinitionIsBeingBuiltSignal(array $sqlString)
    {
        $signalReturn = $this->signalSlotDispatcher->dispatch(__CLASS__, 'tablesDefinitionIsBeingBuilt', [$sqlString]);
        // This is important to support old associated returns
        $signalReturn = array_values($signalReturn);
        $sqlString = $signalReturn[0];
        if (!is_array($sqlString)) {
            throw new Exception\UnexpectedSignalReturnValueTypeException(
                sprintf(
                    'The signal %s of class %s returned a value of type %s, but array was expected.',
                    'tablesDefinitionIsBeingBuilt',
                    __CLASS__,
                    gettype($sqlString)
                ),
                1382351456
            );
        }
        return $sqlString;
    }
}
