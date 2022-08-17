<?php

/**
 * Definitions for routes provided by EXT:reactions
 */
return [
    'reaction' => [
        'path' => '/reaction/{reactionIdentifier?}',
        'access' => 'public',
        'methods' => ['POST'],
        'target' => \TYPO3\CMS\Reactions\Http\ReactionHandler::class . '::handleReaction',
    ],
];
