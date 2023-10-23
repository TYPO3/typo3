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
use TYPO3\CMS\Backend\Routing\RouteResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Authentication\ReactionUserAuthentication;
use TYPO3\CMS\Reactions\Exception\ReactionNotFoundException;
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
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1. We only listen to the "reaction" endpoint
        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute('routing');
        if (!($routeResult instanceof RouteResult) || $routeResult->getRouteName() !== 'reaction') {
            return $handler->handle($request);
        }

        // 2. Security check
        $reactionIdentifier = (string)($routeResult->getArguments()['reactionIdentifier'] ?? '');
        $secretKey = $this->resolveReactionSecret($request);
        if ($secretKey === '' || !Uuid::isValid($reactionIdentifier)) {
            return $this->getFailureResponse('Invalid information', $request);
        }

        $reaction = $this->reactionRepository->getReactionRecordByIdentifier($reactionIdentifier);
        if ($reaction === null) {
            return $this->getFailureResponse('No reaction found for given identifier', $request, 404);
        }

        if (!$reaction->isSecretValid($secretKey)) {
            return $this->getFailureResponse('Invalid secret given', $request, 401);
        }

        // 3. Handle reaction user authentication
        $user = GeneralUtility::makeInstance(ReactionUserAuthentication::class);
        $user->setReactionInstruction($reaction);
        $user->start($request);

        // 4. Handle reaction
        try {
            return $this->reactionHandler->handleReaction($request, $reaction, $user);
        } catch (ReactionNotFoundException $e) {
            return $this->getFailureResponse($e->getMessage(), $request, 404);
        }
    }

    protected function resolveReactionSecret(ServerRequestInterface $request): string
    {
        return $request->getHeaderLine('x-api-key');
    }

    protected function getFailureResponse(
        string $errorMessage,
        ServerRequestInterface $request,
        int $statusCode = 400
    ): ResponseInterface {
        $this->logger->warning($errorMessage, ['request' => $request]);

        return $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(
                $this->streamFactory->createStream((string)json_encode(['success' => false, 'error' => $errorMessage]))
            );
    }
}
