<?php
/**
 * Commands to be executed by typo3, where the key of the array
 * is the name of the command (to be called as the first argument after typo3).
 * Required parameter is the "class" of the command which needs to be a subclass
 * of Symfony/Console/Command. An optional parameter is "user" that logs in
 * a Backend user via CLI.
 */
return [
    'syslog:list' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\ListSysLogCommand::class
    ],
    'cleanup:missingfiles' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\MissingFilesCommand::class,
        // needed for updating the reference index (optional)
        'user' => '_cli_lowlevel'
    ],
    'cleanup:lostfiles' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\LostFilesCommand::class,
        // needed for updating the reference index (optional)
        'user' => '_cli_lowlevel'
    ],
    'cleanup:multiplereferencedfiles' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\FilesWithMultipleReferencesCommand::class,
        // needed for updating the reference index (optional)
        'user' => '_cli_lowlevel'
    ],
    'cleanup:missingrelations' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\MissingRelationsCommand::class,
        // needed for updating the reference index (optional)
        'user' => '_cli_lowlevel'
    ],
    'cleanup:deletedrecords' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\DeletedRecordsCommand::class,
        'user' => '_cli_lowlevel'
    ],
    'cleanup:orphanrecords' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\OrphanRecordsCommand::class,
        'user' => '_cli_lowlevel'
    ],
    'cleanup:flexforms' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\CleanFlexFormsCommand::class,
        'user' => '_cli_lowlevel'
    ]
];
