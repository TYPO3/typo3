<?php

return [
    'dumpautoload' => [
        'class' => \TYPO3\CMS\Core\Command\DumpAutoloadCommand::class,
        'schedulable' => false,
    ],
    'swiftmailer:spool:send' => [
        'class' => \TYPO3\CMS\Core\Command\SendEmailCommand::class,
    ],
];
