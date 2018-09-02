<?php
/**
 * Commands to be executed by typo3, where the key of the array
 * is the name of the command (to be called as the first argument after typo3).
 * Required parameter is the "class" of the command which needs to be a subclass
 * of Symfony/Console/Command.
 *
 * example: bin/typo3 language:update
 */
return [
    'language:update' => [
        'class' => \TYPO3\CMS\Install\Command\LanguagePackCommand::class
    ],
    'upgrade:run' => [
        'class' => \TYPO3\CMS\Install\Command\UpgradeWizardRunCommand::class
    ],
    'upgrade:list' => [
        'class' => \TYPO3\CMS\Install\Command\UpgradeWizardListCommand::class
    ]
];
