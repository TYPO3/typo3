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

namespace TYPO3\CMS\Extbase\Event\Mvc;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Attribute\Authorize;
use TYPO3\CMS\Extbase\Authorization\AuthorizationFailureReason;

/**
 * Event that is triggered when an extbase action authorization check fails and before the authorization denied
 * PropagateResponseException is thrown. Extension developers can use this event to prevent the default behavior
 * and provide a custom response.
 *
 * Security notice: When providing a custom response, it must be ensured, that no objects are persisted using
 * the extbase persistence layer. Additionally it is recommended to only use a custom response for non-cached
 * actions, because otherwise the response result will get cached.
 */
final class BeforeActionAuthorizationDeniedEvent
{
    private ?ResponseInterface $response = null;

    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly string $controllerClassName,
        private readonly string $actionMethodName,
        private readonly Authorize $authorize,
        private readonly AuthorizationFailureReason $failureReason
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getControllerClassName(): string
    {
        return $this->controllerClassName;
    }

    public function getActionMethodName(): string
    {
        return $this->actionMethodName;
    }

    public function getAuthorize(): Authorize
    {
        return $this->authorize;
    }

    public function getFailureReason(): AuthorizationFailureReason
    {
        return $this->failureReason;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }
}
