<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Reactions\Reaction;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;

interface ReactionInterface
{
    /**
     * The reaction type, used for the registry and stored in the database
     */
    public static function getType(): string;

    /**
     * A meaningful description for the reaction
     */
    public static function getDescription(): string;

    /**
     * An icon identifier for the reaction
     */
    public static function getIconIdentifier(): string;

    /**
     * Main method of the reaction, handling the incoming request
     */
    public function react(ServerRequestInterface $request, array $payload, ReactionInstruction $reaction): ResponseInterface;
}
