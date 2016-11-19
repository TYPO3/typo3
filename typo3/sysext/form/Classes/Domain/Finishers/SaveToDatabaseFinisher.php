<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Finishers;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;

/**
 * This finisher saves the data from a submitted form into
 * a database table.
 *
 * Scope: frontend
 */
class SaveToDatabaseFinisher extends AbstractFinisher
{

    /**
     * @var array
     */
    protected $defaultOptions = [
        'table' => null,
        'elements' => [],
    ];

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     *
     * @return void
     * @throws FinisherException
     */
    protected function executeInternal()
    {
        $table = $this->parseOption('table');
        $elementsConfiguration = $this->parseOption('elements');

        $databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $schemaManager = $databaseConnection->getSchemaManager();

        if ($schemaManager->tablesExist([$table]) === false) {
            throw new FinisherException('The table "' . $table . '" does not exist.', 1476362091);
        }

        $databaseColumns = $schemaManager->listTableColumns($table);
        foreach ($elementsConfiguration as $elementIdentifier => $elementConfiguration) {
            if (!array_key_exists($elementConfiguration['mapOnDatabaseColumn'], $databaseColumns)) {
                throw new FinisherException('The column "' . $elementConfiguration['mapOnDatabaseColumn'] . '" does not exist in table "' . $table . '".', 1476362572);
            }
        }

        $formRuntime = $this->finisherContext->getFormRuntime();

        $insertData = [];
        foreach ($this->finisherContext->getFormValues() as $elementIdentifier => $elementValue) {
            $element = $formRuntime->getFormDefinition()->getElementByIdentifier($elementIdentifier);
            if (
                !$element instanceof FormElementInterface
                || !isset($elementsConfiguration[$elementIdentifier])
                || !isset($elementsConfiguration[$elementIdentifier]['mapOnDatabaseColumn'])
            ) {
                continue;
            }

            if ($elementValue instanceof FileReference) {
                if (isset($elementsConfiguration[$elementIdentifier]['saveFileIdentifierInsteadOfUid'])) {
                    $saveFileIdentifierInsteadOfUid = (bool)$elementsConfiguration[$elementIdentifier]['saveFileIdentifierInsteadOfUid'];
                } else {
                    $saveFileIdentifierInsteadOfUid = false;
                }

                if ($saveFileIdentifierInsteadOfUid) {
                    $elementValue = $elementValue->getOriginalResource()->getCombinedIdentifier();
                } else {
                    $elementValue = $elementValue->getOriginalResource()->getProperty('uid_local');
                }
            }
            $insertData[$elementsConfiguration[$elementIdentifier]['mapOnDatabaseColumn']] = $elementValue;
        }

        if (!empty($insertData)) {
            $databaseConnection->insert($table, $insertData);
        }
    }
}
