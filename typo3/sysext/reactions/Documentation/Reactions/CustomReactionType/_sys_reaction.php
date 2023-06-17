<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'sys_reaction',
    'reaction_type',
    [
        'label' => \T3docs\Examples\Reaction\ExampleReactionType::getDescription(),
        'value' => \T3docs\Examples\Reaction\ExampleReactionType::getType(),
        'icon' => \T3docs\Examples\Reaction\ExampleReactionType::getIconIdentifier(),
    ]
);
