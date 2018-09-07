<?php

return [
    'default' => [
        // The DefaultProvider is loaded every time
        // \TYPO3\CMS\Core\ExpressionLanguage\DefaultProvider::class,
    ],
    'typoscript' => [
        \TYPO3\CMS\Core\ExpressionLanguage\TypoScriptConditionProvider::class,
    ],
    'site' => [
        \TYPO3\CMS\Core\ExpressionLanguage\SiteConditionProvider::class,
    ]
];
