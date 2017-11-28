<?php

/**
 * Definitions for routes provided by EXT:documentation
 */
return [
    'documentation_remove' => [
        'path' => '/documentation/remove',
        'target' => \TYPO3\CMS\Documentation\Controller\DocumentController::class . '::deleteAction'
    ],
];
