<?php
/**
 * Commands to be executed by t3console, where the key of the array
 * is the name of the command (to be called as the first argument after t3console).
 * Required parameter is the "class" of the command which needs to be a subclass
 * of Symfony/Console/Command. An optional parameter is "user" that logs in
 * a Backend user via CLI.
 */
return [
    'backend:lock' => [
        'class' => \TYPO3\CMS\Backend\Command\LockBackendCommand::class
    ],
    'backend:unlock' => [
        'class' => \TYPO3\CMS\Backend\Command\LockBackendCommand::class
    ]
];
