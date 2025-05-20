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
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Controller\Security\SudoModeController;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessStorage;
use TYPO3\CMS\Backend\Security\SudoMode\Exception\RequestGrantedException;
use TYPO3\CMS\Backend\Security\SudoMode\Exception\VerificationRequiredException;
use TYPO3\CMS\Core\Http\Application;
use TYPO3\CMS\Core\Http\JsonResponse;
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

    /**
     * @internal
     */
    public ?ServerRequestInterface $currentRequest = null;

    public function __construct(
        private readonly AccessStorage $storage,
        private readonly SudoModeController $controller,
        private readonly ServerRequestFactoryInterface $serverRequestFactory,
        private readonly Application $application
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->currentRequest = $request;
        try {
            $response = $handler->handle($request);
        } catch (VerificationRequiredException $exception) {
            $response = $this->handleVerificationRequired($exception, $request);
        } catch (RequestGrantedException $exception) {
            $response = $this->handleRequestGrantedException($exception, $request);
        }
        $this->currentRequest = null;
        return $response;
    }

    /**
     * Redirects to the sudo mode controller, and renders the password verification dialog.
     */
    private function handleVerificationRequired(
        VerificationRequiredException $exception,
        ServerRequestInterface $request,
    ): ResponseInterface {
        $claim = $exception->getClaim();
        $this->logger->info('Confirmation required', ['claim' => $claim->id]);
        $this->storage->addClaim($claim);
        $isAjaxCall = (bool)($request->getAttribute('route')?->getOption('ajax') ?? false);
        if ($isAjaxCall) {
            return (new JsonResponse([
                'sudoModeInitialization' => [
                    'verifyActionUri' => (string)$this->controller->buildVerifyActionUriForClaim($claim),
                    'allowInstallToolPassword' => $GLOBALS['BE_USER']->isSystemMaintainer(true),
                    'isAjax' => true,
                    'labels' => $GLOBALS['LANG']->getLabelsFromResource('EXT:backend/Resources/Private/Language/SudoMode.xlf'),
                ],
            ]))->withStatus(422, 'Step-Up required: A different authentication level is required');
        }
        $uri = $this->controller->buildModuleActionUriForClaim($claim);
        return new RedirectResponse($uri, 401);
    }

    /**
     * Redirects (GET) or Subrequests (non GET HTTP methods) to the URI that
     * was originally requested (prior to this sudo mode interception).
     */
    private function handleRequestGrantedException(
        RequestGrantedException $exception,
        ServerRequestInterface $request,
    ): ResponseInterface {
        $instruction = $exception->getInstruction();
        if ($instruction->getMethod() === 'GET') {
            return new RedirectResponse($instruction->getUri(), 303);
        }

        $request = $this->serverRequestFactory
            ->createServerRequest(
                $instruction->getMethod(),
                $instruction->getUri(),
                $instruction->getServerParams()
            )
            ->withBody($instruction->getBody())
            ->withParsedBody($instruction->getParsedBody())
            ->withQueryParams($instruction->getQueryParams())
            ->withRequestTarget($instruction->getRequestTarget())
            // Use cookie params from current request, as cookies might have been updated in the meantime
            ->withCookieParams($request->getCookieParams());
        foreach ($instruction->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $request = $request->withAddedHeader($name, $value);
            }
        }
        return $this->application->handle($request);
    }
}
