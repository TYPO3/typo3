<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Finishers\ClosureFinisher;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
class SomeClass
{
    private function addDeleteUploadsFinisherWithMessage(FormDefinition $formDefinition, string $message)
    {
        $formDefinition->createFinisher('SaveToDatabase', [
            'table' => 'fe_users',
            'mode' => 'update',
            'whereClause' => [
                'uid' => 1,
            ],
            'databaseColumnMappings' => [
                'pid' => ['value' => 1],
            ],
            'elements' => [
                'textfield-identifier-1' => ['mapOnDatabaseColumn' => 'first_name'],
                'textfield-identifier-2' => ['mapOnDatabaseColumn' => 'last_name'],
                'textfield-identifier-3' => ['mapOnDatabaseColumn' => 'username'],
                'advancedpassword-1' => [
                    'mapOnDatabaseColumn' => 'password',
                    'skipIfValueIsEmpty' => true,
                    'hashed' => true
                ],
            ],
        ]);
    }
}
