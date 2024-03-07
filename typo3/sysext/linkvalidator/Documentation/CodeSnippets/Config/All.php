<?php

return [
    // Examples
    [
        'action' => 'createCodeSnippet',
        'caption' => 'EXT:examples/Classes/LinkValidator/LinkType/ExampleLinkType.php',
        'sourceFile' => 'EXT:examples/Classes/LinkValidator/LinkType/ExampleLinkType.php',
        'replaceFirstMultilineComment' => true,
        'targetFileName' => 'CodeSnippets/Examples/ExampleLinkType.rst.txt',
    ],
    [
        'action' => 'createCodeSnippet',
        'caption' => 'EXT:examples/Configuration/TsConfig/Page/Extension/Linkvalidator.tsconfig',
        'sourceFile' => 'EXT:examples/Configuration/TsConfig/Page/Extension/Linkvalidator.tsconfig',
        'targetFileName' => 'CodeSnippets/Examples/ActivateCustomLinktypeTsConfig.rst.txt',
        'language' => 'typoscript',
    ],

    // API
    [
        'action' => 'createPhpClassDocs',
        'class' => \TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype::class,
        'targetFileName' => 'CodeSnippets/Api/AbstractLinktype.rst.txt',
        'withCode' => false,
    ],
    [
        'action' => 'createPhpClassDocs',
        'class' => \TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface::class,
        'targetFileName' => 'CodeSnippets/Api/LinktypeInterface.rst.txt',
        'withCode' => false,
    ],
];
