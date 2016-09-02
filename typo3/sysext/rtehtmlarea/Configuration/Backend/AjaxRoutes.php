<?php

/**
 * Definitions for routes provided by EXT:rtehtmlarea
 * Contains all AJAX-based routes for entry points
 */
return [
    'rte_insert_image' => [
        'path' => '/rte/insert-image',
        'target' => \TYPO3\CMS\Rtehtmlarea\Controller\SelectImageController::class . '::buildImageMarkup',
    ],
    // Spellchecker
    'rtehtmlarea_spellchecker' => [
        'path' => '/rte/spellchecker',
        'target' => \TYPO3\CMS\Rtehtmlarea\Controller\SpellCheckingController::class . '::processRequest'
    ],
];
