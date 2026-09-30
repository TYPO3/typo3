<?php

/**
 * Definitions for routes provided by EXT:reactions
 */
return [
    'reaction' => [
        'path' => '/reaction/{reactionIdentifier?}',
        'access' => 'anonymous',
        'methods' => ['POST'],
        'target' => \TYPO3\CMS\Reactions\Http\ReactionHandler::class . '::handleReaction',
    ],
];
