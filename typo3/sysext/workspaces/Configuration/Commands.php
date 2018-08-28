<?php
/**
 * Commands to be executed by the typo3 CLI binary, where the key of the array
 * is the name of the command (to be called as the first argument after "bin/typo3").
 * Required parameter is the "class" of the command which needs to be a subclass
 * of Symfony/Console/Command.
 */
return [
    'cleanup:versions' => [
        'class' => \TYPO3\CMS\Workspaces\Command\WorkspaceVersionRecordsCommand::class,
    ],
    'cleanup:previewlinks' => [
        'class' => \TYPO3\CMS\Workspaces\Command\CleanupPreviewLinksCommand::class,
    ],
    'workspace:autopublish' => [
        'class' => \TYPO3\CMS\Workspaces\Command\AutoPublishCommand::class,
    ],
];
