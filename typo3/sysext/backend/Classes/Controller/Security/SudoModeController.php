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

namespace TYPO3\CMS\Backend\Controller\Security;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessClaim;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessFactory;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessStorage;
use TYPO3\CMS\Backend\Security\SudoMode\Exception\RequestGrantedException;
use TYPO3\CMS\Backend\Security\SudoMode\PasswordVerification;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handling visual sudo mode verification for configured routes/modules.
 *
 * @internal
 */
#[Controller]
final class SudoModeController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const ROUTE_PATH_MODULE = '/sudo-mode/module';
    private const ROUTE_PATH_APPLY = '/sudo-mode/apply';
    private const ROUTE_PATH_ERROR = '/sudo-mode/error';
    private const ROUTE_PATH_VERIFY = '/ajax/sudo-mode/verify';

    public function __construct(
        private readonly UriBuilder $uriBuilder,
        private readonly PageRenderer $pageRenderer,
        private readonly AccessFactory $factory,
        private readonly AccessStorage $storage,
        private readonly PasswordVerification $passwordVerification,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly BackendEntryPointResolver $backendEntryPointResolver,
    ) {
    }

    public function buildModuleActionUriForClaim(AccessClaim $claim): UriInterface
    {
        return $this->uriBuilder->buildUriFromRoutePath(
            self::ROUTE_PATH_MODULE,
            $this->buildUriParametersForClaim($claim, 'module')
        );
    }

    /**
     * Renders the module backend markup, including the `<typo3-backend-security-sudo-mode>` element.
     */
    public function moduleAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/SudoMode.xmlf');

        $claim = $this->resolveClaimFromRequest($request, 'module');
        if ($claim === null) {
            return $this->redirectToErrorAction();
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->assignMultiple([
            'verifyActionUri' => $this->uriBuilder->buildUriFromRoutePath(
                self::ROUTE_PATH_VERIFY,
                $this->buildUriParametersForClaim($claim, 'verify')
            ),
        ]);
        return $view->renderResponse('SudoMode/Module');
    }

    /**
     * Called from JavaScript web-component, throwing an exception that is handled by `SudoModeInterceptor` middleware.
     */
    public function applyAction(ServerRequestInterface $request): ResponseInterface
    {
        // @todo security: action is not signed and can be by-passed easily
        $claim = $this->resolveClaimFromRequest($request, 'apply');
        if ($claim === null) {
            return $this->redirectToErrorAction();
        }

        $this->storage->removeClaim($claim);
        throw (new RequestGrantedException('Replay request', 1605873757))
            ->withInstruction($claim->instruction);
    }

    /**
     * Renders markup with error messages in case `AccessClaim` could not be resolved (e.g. when expired).
     */
    public function errorAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $view->assignMultiple([
            'cancelUri' => $this->backendEntryPointResolver->getPathFromRequest($request),
            'cancelTarget' => '_top',
        ]);
        return $view->renderResponse('SudoMode/Error');
    }

    /**
     * Verifies the provided password, called via AJAX from JavaScript web-component.
     */
    public function verifyAction(ServerRequestInterface $request): ResponseInterface
    {
        $claim = $this->resolveClaimFromRequest($request, 'verify');
        if ($claim === null) {
            return new JsonResponse(['message' => 'bad-request'], 400);
        }

        $password = (string)($request->getParsedBody()['password'] ?? '');
        $useInstallToolPassword = (bool)($request->getParsedBody()['useInstallToolPassword'] ?? false);
        $loggerContext = $this->buildLoggerContext($claim);

        $redirect = [
            'uri' => (string)$this->uriBuilder->buildUriFromRoutePath(
                self::ROUTE_PATH_APPLY,
                $this->buildUriParametersForClaim($claim, 'apply')
            ),
        ];
        if ($useInstallToolPassword && $this->passwordVerification->verifyInstallToolPassword($password)) {
            $this->logger->info('Verified with install tool password', $loggerContext);
            $this->grantClaim($claim);
            return new JsonResponse(['message' => 'accessGranted', 'redirect' => $redirect]);
        }
        if (!$useInstallToolPassword && $this->passwordVerification->verifyBackendUserPassword($password, $this->getBackendUser())) {
            $this->logger->info('Verified with user password', $loggerContext);
            $this->grantClaim($claim);
            return new JsonResponse(['message' => 'accessGranted', 'redirect' => $redirect]);
        }
        return new JsonResponse(['message' => 'invalidPassword'], 403);
    }

    private function redirectToErrorAction(): ResponseInterface
    {
        $uri = $this->uriBuilder->buildUriFromRoutePath(self::ROUTE_PATH_ERROR);
        return new RedirectResponse($uri);
    }

    /**
     * @param string $additionalPepper used to create a specific signature (e.g. on the action)
     */
    private function buildUriParametersForClaim(AccessClaim $claim, string $additionalPepper): array
    {
        $additionalPeppers = [self::class, $additionalPepper];
        return [
            'claim' => $claim->id,
            'hash' => GeneralUtility::hmac($claim->id, json_encode($additionalPeppers)),
        ];
    }

    /**
     * @param string $additionalPepper used to create a specific signature (e.g. on the action)
     */
    private function resolveClaimFromRequest(ServerRequestInterface $request, string $additionalPepper): ?AccessClaim
    {
        $claimId = (string)($request->getQueryParams()['claim'] ?? '');
        $claimHash = (string)($request->getQueryParams()['hash'] ?? '');
        $additionalPeppers = [self::class, $additionalPepper];
        $expectedHash = GeneralUtility::hmac($claimId, json_encode($additionalPeppers));
        if ($claimId === '' ||  $claimHash === '' || !hash_equals($expectedHash, $claimHash)) {
            return null;
        }
        return $this->storage->findClaimById($claimId);
    }

    private function grantClaim(AccessClaim $claim): void
    {
        $grant = $this->factory->buildGrantForSubject($claim->subject);
        $this->storage->addGrant($grant);
    }

    /**
     * @return array<string, int|string>
     */
    private function buildLoggerContext(AccessClaim $claim): array
    {
        $backendUserAspect = GeneralUtility::makeInstance(Context::class)
            ->getAspect('backend.user');
        return [
            'claim' => $claim->id,
            'user' => $backendUserAspect->get('id'),
        ];
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
