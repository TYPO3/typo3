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

namespace TYPO3\CMS\Backend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Controller\Security\SudoModeController;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessStorage;
use TYPO3\CMS\Backend\Security\SudoMode\Exception\RequestGrantedException;
use TYPO3\CMS\Backend\Security\SudoMode\Exception\VerificationRequiredException;
use TYPO3\CMS\Core\Http\RedirectResponse;

/**
 * Middleware that catches any `VerificationRequiredException` (= the current
 * user must verify the access for a particular resource, route, module) by
 * entering their password again; and any `RequestGrantedException` (= the
 * verification process was successful & the user shall be redirected to
 * the URI, that has been requested originally).
 */
final class SudoModeInterceptor implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly AccessStorage $storage,
        private readonly SudoModeController $controller,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (VerificationRequiredException $exception) {
            return $this->handleVerificationRequired($exception);
        } catch (RequestGrantedException $exception) {
            return $this->handleRequestGrantedException($exception);
        }
    }

    /**
     * Redirects to the sudo mode controller, and renders the password verification dialog.
     */
    private function handleVerificationRequired(VerificationRequiredException $exception): ResponseInterface
    {
        $claim = $exception->getClaim();
        $this->logger->info('Confirmation required', ['claim' => $claim->id]);
        $this->storage->addClaim($claim);
        $uri = $this->controller->buildModuleActionUriForClaim($claim);
        return new RedirectResponse($uri, 401);
    }

    /**
     * Redirects to the URI that was originally requested (prior to this sudo mode interception).
     */
    private function handleRequestGrantedException(RequestGrantedException $exception): ?ResponseInterface
    {
        $instruction = $exception->getInstruction();
        // other request methods than HTTP GET are currently not supported
        // (there is much more to do, in terms of intercepting AJAX request etc.)
        if ($instruction->getMethod() === 'GET') {
            return new RedirectResponse($instruction->getUri(), 303);
        }
        return null;
    }
}
