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

use Doctrine\DBAL\Exception as DbalException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Backend\Routing\RouteResult;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Authentication\ReactionUserAuthentication;
use TYPO3\CMS\Reactions\Exception\ReactionNotFoundException;
use TYPO3\CMS\Reactions\Http\ReactionHandler;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\Repository\ReactionRepository;

/**
 * Hooks into the backend request, and checks if a reaction is triggered,
 * if so, jump directly to the ReactionHandler.
 *
 * @internal This is a specific Request controller implementation and is not considered part of the Public TYPO3 API.
 */
readonly class ReactionResolver implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private ReactionHandler $reactionHandler,
        private ReactionRepository $reactionRepository,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1. We only listen to the "reaction" endpoint
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
            $response = $this->reactionHandler->handleReaction($request, $reaction, $user);
        } catch (ReactionNotFoundException $e) {
            $response = $this->getFailureResponse($e->getMessage(), $request, 404);
        } catch (ImmediateResponseException $e) {
            $this->recordCall($reaction, $e->getResponse()->getStatusCode(), $this->extractFailure($e->getResponse()));
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->warning('Reaction "{name}" ({identifier}) of type "{type}" failed unexpectedly: {error}', [
                'name' => $reaction->getName(),
                'identifier' => $reaction->getIdentifier(),
                'type' => $reaction->getType(),
                'error' => $e->getMessage(),
                'exception' => $e,
                'request' => $request,
            ]);
            $this->recordCall($reaction, 500, 'The reaction failed unexpectedly');
            throw $e;
        }
        $this->recordCall($reaction, $response->getStatusCode(), $this->extractFailure($response));
        return $response;
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

    private function recordCall(ReactionInstruction $reaction, int $statusCode, string $failure): void
    {
        try {
            $this->reactionRepository->updateLastCall($reaction->getUid(), $statusCode, $failure);
        } catch (DbalException $e) {
            $this->logger->warning('The call of reaction "{name}" ({identifier}) could not be recorded: {error}', [
                'name' => $reaction->getName(),
                'identifier' => $reaction->getIdentifier(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The reason of a failure is the error the caller was sent, read without moving
     * the body on, so the response goes out as it came in.
     */
    private function extractFailure(ResponseInterface $response): string
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();
        if (($statusCode >= 200 && $statusCode < 300)
            || !$body->isSeekable()
            || ($body->getSize() ?? PHP_INT_MAX) > 65536
            || !str_contains($response->getHeaderLine('Content-Type'), 'json')
        ) {
            return '';
        }
        $position = $body->tell();
        $data = json_decode((string)$body, true);
        $body->seek($position);
        if (!is_array($data) || !is_string($data['error'] ?? null)) {
            return '';
        }
        return mb_strlen($data['error']) > 255 ? mb_substr($data['error'], 0, 254) . '…' : $data['error'];
    }
}
