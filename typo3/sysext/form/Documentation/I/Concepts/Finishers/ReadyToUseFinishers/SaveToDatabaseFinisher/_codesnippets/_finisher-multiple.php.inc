<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Finishers\ClosureFinisher;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
class SomeClass
{
    private function addDeleteUploadsFinisherWithMessage(FormDefinition $formDefinition, string $message)
    {
        $formDefinition->createFinisher('SaveToDatabase', [
            1 => [
                'table' => 'my_table',
                'mode' => 'insert',
                'databaseColumnMappings' => [
                    'some_column' => ['value' => 'cool'],
                ],
            ],
            2 => [
                'table' => 'my_other_table',
                'mode' => 'update',
                'whereClause' => [
                    'pid' => 1,
                ],
                'databaseColumnMappings' => [
                    'some_other_column' => ['value' => '{SaveToDatabase.insertedUids.1}'],
                ],
            ],
        ]);
    }
}
