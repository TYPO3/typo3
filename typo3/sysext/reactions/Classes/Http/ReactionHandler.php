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

namespace TYPO3\CMS\Reactions\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Reactions\Authentication\ReactionUserAuthentication;
use TYPO3\CMS\Reactions\Exception\ReactionNotFoundException;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\ReactionRegistry;

/**
 * Endpoint for triggering the reaction handler.
 *
 * Resolves the payload and calls the actual Reaction Type with the request,
 * the payload, and sends the response in return.
 *
 * At this point, the evaluation etc. all need to have happened.
 *
 * @internal This is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class ReactionHandler
{
    public function __construct(
        private readonly ReactionRegistry $reactionRegistry,
        private readonly LoggerInterface $logger,
        private readonly LanguageServiceFactory $languageServiceFactory
    ) {}

    public function handleReaction(ServerRequestInterface $request, ?ReactionInstruction $reactionInstruction, ReactionUserAuthentication $user): ResponseInterface
    {
        if ($reactionInstruction === null) {
            $this->logger->warning('No reaction given', [
                'request' => $request,
            ]);
            throw new ReactionNotFoundException('No reaction given', 1669757255);
        }
        $reaction = $this->reactionRegistry->getReactionByType($reactionInstruction->getType());
        if ($reaction === null) {
            throw new ReactionNotFoundException('No reaction found for given reaction type', 1662458842);
        }

        // Prepare the user and language object before calling the reaction execution process
        $GLOBALS['LANG'] = $this->languageServiceFactory->createFromUserPreferences($user);
        $GLOBALS['BE_USER'] = $user;

        $payload = $this->getPayload($request);
        $response = $reaction->react($request, $payload, $reactionInstruction);
        $this->logger->info('Reaction was handled successfully', [
            'request' => $request,
        ]);
        return $this->buildReactionResponse($response);
    }

    protected function getPayload(ServerRequestInterface $request): array
    {
        $body = (string)$request->getBody();

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            return is_array($payload) ? $payload : [];
        } catch (\JsonException $e) {
            // do nothing
            return [];
        }
    }

    protected function buildReactionResponse(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('X-TYPO3-Reaction-Success', $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ? 'true' : 'false');
    }
}
