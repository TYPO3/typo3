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

namespace TYPO3\CMS\FrontendLogin\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\PasswordPolicy\Event\EnrichPasswordValidationContextDataEvent;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyValidator;
use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\FrontendLogin\Configuration\RecoveryConfiguration;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\FrontendLogin\Event\PasswordChangeEvent;
use TYPO3\CMS\FrontendLogin\Service\RecoveryService;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class PasswordRecoveryController extends ActionController
{
    public function __construct(
        protected RecoveryService $recoveryService,
        protected FrontendUserRepository $userRepository,
        protected RecoveryConfiguration $recoveryConfiguration,
        protected readonly Features $features,
        protected readonly PageRepository $pageRepository
    ) {}

    /**
     * Shows the recovery form. If $userIdentifier is set, an email will be sent, if the corresponding user exists and
     * has a valid email address set.
     */
    public function recoveryAction(?string $userIdentifier = null): ResponseInterface
    {
        if (empty($userIdentifier)) {
            return $this->htmlResponse();
        }

        $storagePageIds = ($GLOBALS['TYPO3_CONF_VARS']['FE']['checkFeUserPid'] ?? false)
            ? $this->pageRepository->getPageIdsRecursive(GeneralUtility::intExplode(',', (string)($this->settings['pages'] ?? ''), true), (int)($this->settings['recursive'] ?? 0))
            : [];

        $userData = $this->userRepository->findUserByUsernameOrEmailOnPages($userIdentifier, $storagePageIds);

        if ($userData && GeneralUtility::validEmail($userData['email'])) {
            $hash = $this->recoveryConfiguration->getForgotHash();
            $this->userRepository->updateForgotHashForUserByUid($userData['uid'], $this->hashService->hmac($hash, self::class));
            $this->recoveryService->sendRecoveryEmail($this->request, $userData, $hash);
        }

        $this->addFlashMessage($this->getTranslation('forgot_reset_message_emailSent'));

        return $this->redirect('login', 'Login', 'felogin');
    }

    /**
     * Validate the hash argument and make sure that:
     *
     * - it is in the expected format
     * - it is not expired
     * - a fe_user with the given hash exists
     *
     * If one of the checks fail, a redirect response to the recoveryAction() is returned
     */
    protected function validateHashArgument(): ?ResponseInterface
    {
        $hash = $this->request->hasArgument('hash') ? $this->request->getArgument('hash') : '';
        $hash = is_string($hash) ? $hash : '';

        if (!$this->validateHashFormat($hash)) {
            return $this->redirect('recovery', 'PasswordRecovery', 'felogin');
        }

        $timestamp = (int)GeneralUtility::trimExplode('|', $hash)[0];
        $currentTimestamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');

        // timestamp is expired or hash can not be assigned to a user
        if ($currentTimestamp > $timestamp || !$this->userRepository->existsUserWithHash($this->hashService->hmac($hash, self::class))) {
            /** @var ExtbaseRequestParameters $extbaseRequestParameters */
            $extbaseRequestParameters = clone $this->request->getAttribute('extbase');
            $originalResult = $extbaseRequestParameters->getOriginalRequestMappingResults();
            $originalResult->addError(new Error($this->getTranslation('change_password_notvalid_message'), 1554994253));
            $extbaseRequestParameters->setOriginalRequestMappingResults($originalResult);
            $this->request = $this->request->withAttribute('extbase', $extbaseRequestParameters);

            return (new ForwardResponse('recovery'))
                ->withControllerName('PasswordRecovery')
                ->withExtensionName('felogin')
                ->withArgumentsValidationResult($originalResult);
        }

        return null;
    }

    /**
     * Show the change password form if a valid hash is available.
     */
    public function showChangePasswordAction(string $hash = ''): ResponseInterface
    {
        // Validate hash (lifetime, format and fe_user with hash persistence)
        if (($response = $this->validateHashArgument()) instanceof ResponseInterface) {
            return $response;
        }

        $this->view->assignMultiple([
            'hash' => $hash,
            'passwordRequirements' => $this->getPasswordPolicyValidator()->getRequirements(),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Validates the hash argument, the entered password and passwordRepeat values. If one of the values is considered
     * as invalid, a response object with validation errors in the mapping results is returned.
     *
     * @throws NoSuchArgumentException
     */
    public function validateHashAndPasswords()
    {
        // Validate hash (lifetime, format and fe_user with hash persistence)
        if (($response = $this->validateHashArgument()) instanceof ResponseInterface) {
            return $response;
        }

        // Exit early if newPass or newPassRepeat is not set.
        /** @var ExtbaseRequestParameters $extbaseRequestParameters */
        $extbaseRequestParameters = clone $this->request->getAttribute('extbase');
        $originalResult = $extbaseRequestParameters->getOriginalRequestMappingResults();
        $argumentsExist = $this->request->hasArgument('newPass') && $this->request->hasArgument('newPassRepeat');
        $argumentsEmpty = empty($this->request->getArgument('newPass')) || empty($this->request->getArgument('newPassRepeat'));

        if (!$argumentsExist || $argumentsEmpty) {
            $originalResult->addError(new Error(
                $this->getTranslation('empty_password_and_password_repeat'),
                1554971665
            ));

            return (new ForwardResponse('showChangePassword'))
                ->withControllerName('PasswordRecovery')
                ->withExtensionName('felogin')
                ->withArguments(['hash' => $this->request->getArgument('hash')])
                ->withArgumentsValidationResult($originalResult);
        }

        $this->validateNewPassword($originalResult);

        // if an error exists, forward with all messages to the change password form
        if ($originalResult->hasErrors()) {
            return (new ForwardResponse('showChangePassword'))
                ->withControllerName('PasswordRecovery')
                ->withExtensionName('felogin')
                ->withArguments(['hash' => $this->request->getArgument('hash')])
                ->withArgumentsValidationResult($originalResult);
        }
    }

    /**
     * Change actual password. Hash $newPass and update the user with the corresponding $hash.
     *
     * @throws AspectNotFoundException
     * @throws InvalidPasswordHashException
     */
    public function changePasswordAction(string $newPass, string $hash): ResponseInterface
    {
        if (($response = $this->validateHashAndPasswords()) instanceof ResponseInterface) {
            return $response;
        }

        $hashedPassword = GeneralUtility::makeInstance(PasswordHashFactory::class)
            ->getDefaultHashInstance('FE')
            ->getHashedPassword($newPass);

        $user = $this->userRepository->findOneByForgotPasswordHash($this->hashService->hmac($hash, self::class));
        $event = new PasswordChangeEvent($user, $hashedPassword, $newPass, $this->request);
        $this->eventDispatcher->dispatch($event);

        $this->userRepository->updatePasswordAndInvalidateHash($this->hashService->hmac($hash, self::class), $hashedPassword);
        $this->invalidateUserSessions($user['uid']);

        $this->addFlashMessage($this->getTranslation('change_password_done_message'));

        return $this->redirect('login', 'Login', 'felogin', ['redirectReferrer' => 'off']);
    }

    /**
     * @throws NoSuchArgumentException
     */
    protected function validateNewPassword(Result $originalResult): void
    {
        $newPass = $this->request->getArgument('newPass');

        // make sure the user entered the password twice
        if ($newPass !== $this->request->getArgument('newPassRepeat')) {
            $originalResult->addError(new Error($this->getTranslation('password_must_match_repeated'), 1554912163));
        }

        $hash = $this->request->getArgument('hash');
        $userData = $this->userRepository->findOneByForgotPasswordHash($this->hashService->hmac($hash, self::class));

        // Validate against password policy
        $passwordPolicyValidator = $this->getPasswordPolicyValidator();
        $contextData = new ContextData(
            loginMode: 'FE',
            currentPasswordHash: $userData['password']
        );
        $contextData->setData('currentUsername', $userData['username']);
        $contextData->setData('currentFirstname', $userData['first_name']);
        $contextData->setData('currentLastname', $userData['last_name']);
        $event = $this->eventDispatcher->dispatch(
            new EnrichPasswordValidationContextDataEvent(
                $contextData,
                $userData,
                self::class
            )
        );
        $contextData = $event->getContextData();

        if (!$passwordPolicyValidator->isValidPassword($newPass, $contextData)) {
            foreach ($passwordPolicyValidator->getValidationErrors() as $validationError) {
                $validationResult = new Result();
                $validationResult->addError(new Error($validationError, 1667647475));
                $originalResult->merge($validationResult);
            }
        }
    }

    /**
     * Wrapper to mock LocalizationUtility::translate
     */
    protected function getTranslation(string $key): string
    {
        return (string)LocalizationUtility::translate($key, 'felogin');
    }

    /**
     * Validates that $hash is in the expected format (timestamp|forgot_hash)
     */
    protected function validateHashFormat(string $hash): bool
    {
        return !empty($hash) && strpos($hash, '|') === 10;
    }

    /**
     * Invalidate all frontend user sessions by given user id
     */
    protected function invalidateUserSessions(int $userId): void
    {
        $sessionManager = GeneralUtility::makeInstance(SessionManager::class);
        $sessionBackend = $sessionManager->getSessionBackend('FE');
        $sessionManager->invalidateAllSessionsByUserId($sessionBackend, $userId);
    }

    protected function getPasswordPolicyValidator(): PasswordPolicyValidator
    {
        $passwordPolicy = $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy'] ?? 'default';
        return GeneralUtility::makeInstance(
            PasswordPolicyValidator::class,
            PasswordPolicyAction::UPDATE_USER_PASSWORD,
            is_string($passwordPolicy) ? $passwordPolicy : ''
        );
    }
}
