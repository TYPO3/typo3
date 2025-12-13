<?php

return [
    'TYPO3Tests\\ComponentsTest\\Components' => [
        'templatePaths' => [10 => 'EXT:components_test/Resources/Private/Components/'],
    ],
    'TYPO3Tests\\ComponentsTest\\ComponentsAdditionalArguments' => [
        'templatePaths' => [10 => 'EXT:components_test/Resources/Private/Components/'],
        'additionalArgumentsAllowed' => true,
    ],
    'TYPO3Tests\\ComponentsTest\\AlternativeStructure' => [
        'templatePaths' => [10 => 'EXT:components_test/Resources/Private/AlternativeStructure/'],
        'templateNamePattern' => '{path}/{name}',
    ],
    'TYPO3Tests\\ComponentsTest\\Components\\LegacyComponentCollection' => [
        'templatePaths' => [10 => 'EXT:components_test/Resources/Private/Components/'],
    ],
];
