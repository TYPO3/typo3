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

namespace TYPO3\CMS\Reactions\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Authentication\ReactionUserAuthentication;
use TYPO3\CMS\Reactions\Http\ReactionHandler;
use TYPO3\CMS\Reactions\Repository\ReactionRepository;

/**
 * Hooks into the backend request, and checks if a reaction is triggered,
 * if so, jump directly to the ReactionHandler.
 *
 * @internal This is a specific Request controller implementation and is not considered part of the Public TYPO3 API.
 */
class ReactionResolver implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ReactionHandler $reactionHandler,
        private readonly ReactionRepository $reactionRepository,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1. We only listen to the "reaction" endpoint
        $route = $request->getAttribute('route');
        if ($route->getOption('_identifier') !== 'reaction') {
            return $handler->handle($request);
        }

        // 2. Security check
        $reactionIdentifier = $this->resolveReactionIdentifier($request);
        $secretKey = $this->resolveReactionSecret($request);
        if ($secretKey === '' || $reactionIdentifier === null || !Uuid::isValid($reactionIdentifier)) {
            return $this->jsonResponse(['Invalid information'], 503);
        }

        $reaction = $this->reactionRepository->getReactionRecordByIdentifier($reactionIdentifier);
        if ($reaction === null) {
            $this->logger->warning('No reaction found for given identifier', [
                'request' => $request,
            ]);
            return $this->jsonResponse(['No reaction found for given identifier'], 503);
        }

        if (!$reaction->isSecretValid($secretKey)) {
            $this->logger->error('Invalid secret given', [
                'request' => $request,
            ]);
            return $this->jsonResponse(['Invalid secret given'], 503);
        }

        // 3. Handle reaction user authentication
        $user = GeneralUtility::makeInstance(ReactionUserAuthentication::class);
        $user->setReactionInstruction($reaction);
        $user->start($request);

        // 4. Handle reaction
        return $this->reactionHandler->handleReaction($request, $reaction, $user);
    }

    protected function resolveReactionIdentifier(ServerRequestInterface $request): ?string
    {
        // @todo: this should be handled in Backend Routing in the future
        [$path, $reactionId] = GeneralUtility::revExplode('/', $request->getUri()->getPath(), 2);
        return $reactionId !== '' ? $reactionId : null;
    }

    protected function resolveReactionSecret(ServerRequestInterface $request): string
    {
        return $request->getHeaderLine('x-api-key');
    }

    protected function jsonResponse(array $data, int $statusCode = 200): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream((string)json_encode($data)));
    }
}
