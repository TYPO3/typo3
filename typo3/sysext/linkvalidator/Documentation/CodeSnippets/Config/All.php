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
];
