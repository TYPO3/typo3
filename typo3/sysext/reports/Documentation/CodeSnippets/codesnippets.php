<?php

// See https://github.com/TYPO3-Documentation/t3docs-codesnippets
// ddev exec vendor/bin/typo3  restructured_api_tools:php_domain public/fileadmin/reports/Documentation/CodeSnippets/

return [
    [
        'action' => 'createPhpClassDocs',
        'class' => \TYPO3\CMS\Reports\ReportInterface::class,
        'targetFileName' => 'Generated/ReportInterface.rst.txt',
        'withCode' => false,
    ],
    [
        'action' => 'createPhpClassDocs',
        'class' => \TYPO3\CMS\Reports\StatusProviderInterface::class,
        'targetFileName' => 'Generated/StatusProviderInterface.rst.txt',
        'withCode' => false,
    ],
    [
        'action' => 'createPhpClassDocs',
        'class' => \TYPO3\CMS\Reports\RequestAwareReportInterface::class,
        'targetFileName' => 'Generated/RequestAwareReportInterface.rst.txt',
        'withCode' => false,
    ],
    [
        'action' => 'createPhpClassDocs',
        'class' => \TYPO3\CMS\Reports\RequestAwareStatusProviderInterface::class,
        'targetFileName' => 'Generated/RequestAwareStatusProviderInterface.rst.txt',
        'withCode' => false,
    ],
    [
        'action' => 'createPhpClassDocs',
        'class' => \TYPO3\CMS\Reports\ExtendedStatusProviderInterface::class,
        'targetFileName' => 'Generated/ExtendedStatusProviderInterface.rst.txt',
        'withCode' => false,
    ],
];
