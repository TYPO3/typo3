<?php

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

declare(strict_types=1);

namespace TYPO3\CMS\Core\Authentication\Mfa\Provider;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Authentication\Mfa\MfaViewType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * MFA provider for authentication with recovery codes
 *
 * @internal should only be used by the TYPO3 Core
 */
class RecoveryCodesProvider implements MfaProviderInterface
{
    protected MfaProviderRegistry $mfaProviderRegistry;
    protected Context $context;
    protected UriBuilder $uriBuilder;
    protected FlashMessageService $flashMessageService;

    public function __construct(
        MfaProviderRegistry $mfaProviderRegistry,
        Context $context,
        UriBuilder $uriBuilder,
        FlashMessageService $flashMessageService
    ) {
        $this->mfaProviderRegistry = $mfaProviderRegistry;
        $this->context = $context;
        $this->uriBuilder = $uriBuilder;
        $this->flashMessageService = $flashMessageService;
    }

    private const MAX_ATTEMPTS = 3;

    /**
     * Check if a recovery code is given in the current request
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function canProcess(ServerRequestInterface $request): bool
    {
        return $this->getRecoveryCode($request) !== '';
    }

    /**
     * Evaluate if the provider is activated by checking the
     * active state from the provider properties. This provider
     * furthermore has a mannerism that it only works if at least
     * one other MFA provider is activated for the user.
     *
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function isActive(MfaProviderPropertyManager $propertyManager): bool
    {
        return (bool)$propertyManager->getProperty('active')
            && $this->activeProvidersExist($propertyManager);
    }

    /**
     * Evaluate if the provider is temporarily locked by checking
     * the current attempts state from the provider properties and
     * if there are still recovery codes left.
     *
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function isLocked(MfaProviderPropertyManager $propertyManager): bool
    {
        $attempts = (int)$propertyManager->getProperty('attempts', 0);
        $codes = (array)$propertyManager->getProperty('codes', []);

        // Assume the provider is locked in case either the maximum attempts are exceeded or no codes
        // are available. A provider however can only be locked if set up - an entry exists in database.
        return $propertyManager->hasProviderEntry() && ($attempts >= self::MAX_ATTEMPTS || $codes === []);
    }

    /**
     * Verify the given recovery code and remove it from the
     * provider properties if valid.
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     *
     * @return bool
     */
    public function verify(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        if (!$this->isActive($propertyManager) || $this->isLocked($propertyManager)) {
            // Can not verify an inactive or locked provider
            return false;
        }

        $recoveryCode = $this->getRecoveryCode($request);
        $codes = $propertyManager->getProperty('codes', []);
        $recoveryCodes = GeneralUtility::makeInstance(RecoveryCodes::class, $this->getMode($propertyManager));
        if (!$recoveryCodes->verifyRecoveryCode($recoveryCode, $codes)) {
            $attempts = $propertyManager->getProperty('attempts', 0);
            $propertyManager->updateProperties(['attempts' => ++$attempts]);
            return false;
        }

        // Since the codes were passed by reference to the verify method, the matching code was
        // unset so we simply need to write the array back. However, if the update fails, we must
        // return FALSE even if the authentication was successful to prevent data inconsistency.
        return $propertyManager->updateProperties([
            'codes' => $codes,
            'attempts' => 0,
            'lastUsed' => $this->context->getPropertyFromAspect('date', 'timestamp'),
        ]);
    }

    /**
     * Render the provider specific response for the given content type
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @param string $type
     * @return ResponseInterface
     * @throws PropagateResponseException
     */
    public function handleRequest(
        ServerRequestInterface $request,
        MfaProviderPropertyManager $propertyManager,
        string $type
    ): ResponseInterface {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths(['EXT:core/Resources/Private/Templates/Authentication/MfaProvider/RecoveryCodes']);
        switch ($type) {
            case MfaViewType::SETUP:
                if (!$this->activeProvidersExist($propertyManager)) {
                    // If no active providers are present for the current user, add a flash message and redirect
                    $lang = $this->getLanguageService();
                    $this->addFlashMessage(
                        $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mfa_provider.xlf:setup.recoveryCodes.noActiveProviders.message'),
                        $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mfa_provider.xlf:setup.recoveryCodes.noActiveProviders.title'),
                        FlashMessage::WARNING
                    );
                    if (($normalizedParams = $request->getAttribute('normalizedParams'))) {
                        $returnUrl = $normalizedParams->getHttpReferer();
                    } else {
                        // @todo this will not work for FE - make this more generic!
                        $returnUrl = $this->uriBuilder->buildUriFromRoute('mfa');
                    }
                    throw new PropagateResponseException(new RedirectResponse($returnUrl, 303), 1612883326);
                }
                $codes = GeneralUtility::makeInstance(RecoveryCodes::class, $this->getMode($propertyManager))->generatePlainRecoveryCodes();
                $view->setTemplate('Setup');
                $view->assignMultiple([
                    'recoveryCodes' => implode(PHP_EOL, $codes),
                    // Generate hmac of the recovery codes to prevent them from being changed in the setup from
                    'checksum' => GeneralUtility::hmac(json_encode($codes) ?: '', 'recovery-codes-setup'),
                ]);
                break;
            case MfaViewType::EDIT:
                $view->setTemplate('Edit');
                $view->assignMultiple([
                    'name' => $propertyManager->getProperty('name'),
                    'amountOfCodesLeft' => count($propertyManager->getProperty('codes', [])),
                    'lastUsed' => $this->getDateTime($propertyManager->getProperty('lastUsed', 0)),
                    'updated' => $this->getDateTime($propertyManager->getProperty('updated', 0)),
                ]);
                break;
            case MfaViewType::AUTH:
                $view->setTemplate('Auth');
                $view->assign('isLocked', $this->isLocked($propertyManager));
                break;
        }
        return new HtmlResponse($view->assign('providerIdentifier', $propertyManager->getIdentifier())->render());
    }

    /**
     * Activate the provider by hashing and storing the given recovery codes
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function activate(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        if ($this->isActive($propertyManager)) {
            // Can not activate an active provider
            return false;
        }

        if (!$this->activeProvidersExist($propertyManager)) {
            // Can not activate since no other provider is activated yet
            return false;
        }

        $recoveryCodes = GeneralUtility::trimExplode(PHP_EOL, (string)($request->getParsedBody()['recoveryCodes'] ?? ''));
        $checksum = (string)($request->getParsedBody()['checksum'] ?? '');
        if ($recoveryCodes === []
            || !hash_equals(GeneralUtility::hmac(json_encode($recoveryCodes) ?: '', 'recovery-codes-setup'), $checksum)
        ) {
            // Return since the request does not contain the initially created recovery codes
            return false;
        }

        // Hash given plain recovery codes and prepare the properties array with active state and custom name
        $hashedCodes = GeneralUtility::makeInstance(RecoveryCodes::class, $this->getMode($propertyManager))->generatedHashedRecoveryCodes($recoveryCodes);
        $properties = ['codes' => $hashedCodes, 'active' => true];
        if (($name = (string)($request->getParsedBody()['name'] ?? '')) !== '') {
            $properties['name'] = $name;
        }

        // Usually there should be no entry if the provider is not activated, but to prevent the
        // provider from being unable to activate again, we update the existing entry in such case.
        return $propertyManager->hasProviderEntry()
            ? $propertyManager->updateProperties($properties)
            : $propertyManager->createProviderEntry($properties);
    }

    /**
     * Handle the deactivate action by removing the provider entry
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function deactivate(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        // Only check for the active property here to enable bulk deactivation,
        // e.g. in FormEngine. Otherwise it would not be possible to deactivate
        // this provider if the last "fully" provider was deactivated before.
        if (!(bool)$propertyManager->getProperty('active')) {
            // Can not deactivate an inactive provider
            return false;
        }

        // Delete the provider entry
        return $propertyManager->deleteProviderEntry();
    }

    /**
     * Handle the unlock action by resetting the attempts
     * provider property and issuing new codes.
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function unlock(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        if (!$this->isActive($propertyManager) || !$this->isLocked($propertyManager)) {
            // Can not unlock an inactive or not locked provider
            return false;
        }

        // Reset attempts
        if ((int)$propertyManager->getProperty('attempts', 0) !== 0
            && !$propertyManager->updateProperties(['attempts' => 0])
        ) {
            // Could not reset the attempts, so we can not unlock the provider
            return false;
        }

        // Regenerate codes
        if ($propertyManager->getProperty('codes', []) === []) {
            // Generate new codes and store the hashed ones
            $recoveryCodes = GeneralUtility::makeInstance(RecoveryCodes::class, $this->getMode($propertyManager))->generateRecoveryCodes();
            if (!$propertyManager->updateProperties(['codes' => array_values($recoveryCodes)])) {
                // Codes could not be stored, so we can not unlock the provider
                return false;
            }
            // Add the newly generated codes to a flash message so the user can copy them
            $lang = $this->getLanguageService();
            $this->addFlashMessage(
                sprintf(
                    $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mfa_provider.xlf:unlock.recoveryCodes.message'),
                    implode(' ', array_keys($recoveryCodes))
                ),
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mfa_provider.xlf:unlock.recoveryCodes.title'),
                FlashMessage::WARNING
            );
        }

        return true;
    }

    public function update(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        if (!$this->isActive($propertyManager) || $this->isLocked($propertyManager)) {
            // Can not update an inactive or locked provider
            return false;
        }

        $name = (string)($request->getParsedBody()['name'] ?? '');
        if ($name !== '' && !$propertyManager->updateProperties(['name' => $name])) {
            return false;
        }

        if ((bool)($request->getParsedBody()['regenerateCodes'] ?? false)) {
            // Generate new codes and store the hashed ones
            $recoveryCodes = GeneralUtility::makeInstance(RecoveryCodes::class, $this->getMode($propertyManager))->generateRecoveryCodes();
            if (!$propertyManager->updateProperties(['codes' => array_values($recoveryCodes)])) {
                // Codes could not be stored, so we can not update the provider
                return false;
            }
            // Add the newly generated codes to a flash message so the user can copy them
            $lang = $this->getLanguageService();
            $this->addFlashMessage(
                sprintf(
                    $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mfa_provider.xlf:update.recoveryCodes.message'),
                    implode(' ', array_keys($recoveryCodes))
                ),
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mfa_provider.xlf:update.recoveryCodes.title'),
                FlashMessage::OK
            );
        }

        // Provider properties successfully updated
        return true;
    }

    /**
     * Check if the current user has other active providers
     *
     * @param MfaProviderPropertyManager $currentPropertyManager
     * @return bool
     */
    protected function activeProvidersExist(MfaProviderPropertyManager $currentPropertyManager): bool
    {
        $user = $currentPropertyManager->getUser();
        foreach ($this->mfaProviderRegistry->getProviders() as $identifier => $provider) {
            $propertyManager = MfaProviderPropertyManager::create($provider, $user);
            if ($identifier !== $currentPropertyManager->getIdentifier() && $provider->isActive($propertyManager)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Internal helper method for fetching the recovery code from the request
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function getRecoveryCode(ServerRequestInterface $request): string
    {
        return trim((string)($request->getQueryParams()['rc'] ?? $request->getParsedBody()['rc'] ?? ''));
    }

    /**
     * Determine the mode (used for the hash instance) based on the current users table
     *
     * @param MfaProviderPropertyManager $propertyManager
     * @return string
     */
    protected function getMode(MfaProviderPropertyManager $propertyManager): string
    {
        return $propertyManager->getUser()->loginType;
    }

    /**
     * Add a custom flash message for this provider
     * Note: The flash messages added by the main controller are still shown to the user.
     *
     * @param string $message
     * @param string $title
     * @param int $severity
     */
    protected function addFlashMessage(string $message, string $title = '', int $severity = FlashMessage::INFO): void
    {
        $this->flashMessageService->getMessageQueueByIdentifier()->enqueue(
            GeneralUtility::makeInstance(FlashMessage::class, $message, $title, $severity, true)
        );
    }

    /**
     * Return the timestamp as local time (date string) by applying the globally configured format
     *
     * @param int $timestamp
     * @return string
     */
    protected function getDateTime(int $timestamp): string
    {
        if ($timestamp === 0) {
            return '';
        }

        return date(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
            $timestamp
        ) ?: '';
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
