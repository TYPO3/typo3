<?php
/**
 * Commands to be executed by typo3, where the key of the array
 * is the name of the command (to be called as the first argument after typo3).
 * Required parameter is the "class" of the command which needs to be a subclass
 * of Symfony/Console/Command.
 */
return [
    '_core_command' => [
        'class' => \TYPO3\CMS\Extbase\Command\CoreCommand::class
    ],
    // Overriding Symfony Help command to use Extbase-specific output
    '_extbase_help' => [
        'class' => \TYPO3\CMS\Extbase\Command\HelpCommand::class
    ]
];
