<?php
/**
 * Commands to be executed by typo3, where the key of the array
 * is the name of the command (to be called as the first argument after typo3).
 * Required parameter is the "class" of the command which needs to be a subclass
 * of Symfony/Console/Command.
 *
 * example: bin/typo3 backend:lock
 */
return [
    'backend:lock' => [
        'class' => \TYPO3\CMS\Backend\Command\LockBackendCommand::class
    ],
    'backend:unlock' => [
        'class' => \TYPO3\CMS\Backend\Command\UnlockBackendCommand::class
    ],
    'referenceindex:update' => [
        'class' => \TYPO3\CMS\Backend\Command\ReferenceIndexUpdateCommand::class
    ]
];
