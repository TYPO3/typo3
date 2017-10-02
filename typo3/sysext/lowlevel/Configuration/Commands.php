<?php
/**
 * Commands to be executed by the typo3 CLI binary, where the key of the array
 * is the name of the command (to be called as the first argument after "bin/typo3").
 * Required parameter is the "class" of the command which needs to be a subclass
 * of Symfony/Console/Command.
 */
return [
    'syslog:list' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\ListSysLogCommand::class
    ],
    'cleanup:missingfiles' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\MissingFilesCommand::class
    ],
    'cleanup:lostfiles' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\LostFilesCommand::class
    ],
    'cleanup:multiplereferencedfiles' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\FilesWithMultipleReferencesCommand::class
    ],
    'cleanup:rteimages' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\RteImagesCommand::class
    ],
    'cleanup:missingrelations' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\MissingRelationsCommand::class
    ],
    'cleanup:deletedrecords' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\DeletedRecordsCommand::class
    ],
    'cleanup:orphanrecords' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\OrphanRecordsCommand::class
    ],
    'cleanup:flexforms' => [
        'class' => \TYPO3\CMS\Lowlevel\Command\CleanFlexFormsCommand::class,
    ]
];
