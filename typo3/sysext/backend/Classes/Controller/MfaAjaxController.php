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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Controller to manipulate MFA providers via AJAX in the backend
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class MfaAjaxController
{
    private const ALLOWED_ACTIONS = ['deactivate'];

    protected MfaProviderRegistry $mfaProviderRegistry;

    public function __construct(MfaProviderRegistry $mfaProviderRegistry)
    {
        $this->mfaProviderRegistry = $mfaProviderRegistry;
    }

    /**
     * Main entry point, checking prerequisite and dispatching to the requested action
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $action = (string)($request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? '');

        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            return new JsonResponse($this->getResponseData(false, $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:ajax.invalidRequest')));
        }

        $userId = (int)($request->getParsedBody()['userId'] ?? 0);
        $tableName = (string)($request->getParsedBody()['tableName'] ?? '');

        if (!$userId || !in_array($tableName, ['be_users', 'fe_users'], true)) {
            return new JsonResponse($this->getResponseData(false, $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:ajax.invalidRequest')));
        }

        $user = $this->initializeUser($userId, $tableName);

        if (!$this->isAllowedToPerformAction($action, $user)) {
            return new JsonResponse($this->getResponseData(false, $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:ajax.insufficientPermissions')));
        }

        return new JsonResponse($this->{$action . 'Action'}($request, $user));
    }

    /**
     * Deactivate MFA providers
     * If the request contains a provider, it will be deactivated.
     * Otherwise all active providers are deactivated.
     */
    protected function deactivateAction(ServerRequestInterface $request, AbstractUserAuthentication $user): array
    {
        $lang = $this->getLanguageService();
        $userName = (string)($user->user[$user->username_column] ?? '');
        $providerToDeactivate = (string)($request->getParsedBody()['provider'] ?? '');

        if ($providerToDeactivate === '') {
            // In case no provider is given, try to deactivate all active providers
            $providersToDeactivate = $this->mfaProviderRegistry->getActiveProviders($user);
            if ($providersToDeactivate === []) {
                return $this->getResponseData(
                    false,
                    $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:ajax.deactivate.providersNotDeactivated'),
                    $user
                );
            }
            foreach ($providersToDeactivate as $identifier => $provider) {
                $propertyManager = MfaProviderPropertyManager::create($provider, $user);
                if (!$provider->deactivate($request, $propertyManager)) {
                    return $this->getResponseData(
                        false,
                        sprintf($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:ajax.deactivate.providerNotDeactivated'), $lang->sL($provider->getTitle())),
                        $user
                    );
                }
            }
            return $this->getResponseData(
                true,
                sprintf($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:ajax.deactivate.providersDeactivated'), $userName),
                $user
            );
        }

        if (!$this->mfaProviderRegistry->hasProvider($providerToDeactivate)) {
            return $this->getResponseData(
                false,
                sprintf($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:ajax.deactivate.providerNotFound'), $providerToDeactivate),
                $user
            );
        }

        $provider = $this->mfaProviderRegistry->getProvider($providerToDeactivate);
        $propertyManager = MfaProviderPropertyManager::create($provider, $user);

        if (!$provider->isActive($propertyManager) || !$provider->deactivate($request, $propertyManager)) {
            return $this->getResponseData(
                false,
                sprintf($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:ajax.deactivate.providerNotDeactivated'), $lang->sL($provider->getTitle())),
                $user
            );
        }

        return $this->getResponseData(
            true,
            sprintf($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:ajax.deactivate.providerDeactivated'), $lang->sL($provider->getTitle()), $userName),
            $user
        );
    }

    /**
     * Initialize a user based on the table name
     */
    protected function initializeUser(int $userId, string $tableName): AbstractUserAuthentication
    {
        $user = $tableName === 'be_users'
            ? GeneralUtility::makeInstance(BackendUserAuthentication::class)
            : GeneralUtility::makeInstance(FrontendUserAuthentication::class);

        $user->enablecolumns = ['deleted' => true];
        $user->setBeUserByUid($userId);

        return $user;
    }

    /**
     * Prepare response data for a JSON response
     */
    protected function getResponseData(bool $success, string $message, ?AbstractUserAuthentication $user = null): array
    {
        $payload = [
            'success' => $success,
            'status' => (new FlashMessageQueue('backend'))->enqueue(
                new FlashMessage(
                    $message,
                    $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:ajax.' . ($success ? 'success' : 'error'))
                )
            ),
        ];

        if ($user !== null) {
            $payload['remaining'] = count($this->mfaProviderRegistry->getActiveProviders($user));
        }

        return $payload;
    }

    /**
     * Check if the current logged in user is allowed to perform
     * the requested action on the selected user.
     */
    protected function isAllowedToPerformAction(string $action, AbstractUserAuthentication $user): bool
    {
        if ($action === 'deactivate') {
            $currentBackendUser = $this->getBackendUser();
            // Only admins are allowed to deactivate providers
            if (!$currentBackendUser->isAdmin()) {
                return false;
            }
            // Providers from system maintainers can only be deactivated by system maintainers.
            // This check is however only be necessary if the target is a backend user.
            if ($user instanceof BackendUserAuthentication) {
                $systemMaintainers = array_map('intval', $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] ?? []);
                $isTargetUserSystemMaintainer = $user->isAdmin() && in_array((int)$user->user[$user->userid_column], $systemMaintainers, true);
                if ($isTargetUserSystemMaintainer && !$this->getBackendUser()->isSystemMaintainer()) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
