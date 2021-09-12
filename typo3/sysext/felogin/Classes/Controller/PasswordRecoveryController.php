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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\FrontendLogin\Event\PasswordChangeEvent;
use TYPO3\CMS\FrontendLogin\Service\RecoveryServiceInterface;
use TYPO3\CMS\FrontendLogin\Service\ValidatorResolverService;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class PasswordRecoveryController extends AbstractLoginFormController
{
    /**
     * @var RecoveryServiceInterface
     */
    protected $recoveryService;

    /**
     * @var FrontendUserRepository
     */
    protected $userRepository;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        RecoveryServiceInterface $recoveryService,
        FrontendUserRepository $userRepository
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->recoveryService = $recoveryService;
        $this->userRepository = $userRepository;
    }

    /**
     * Shows the recovery form. If $userIdentifier is set an email will be sent, if the corresponding user exists
     *
     * @param string|null $userIdentifier
     *
     * @return ResponseInterface|void
     *
     * @throws StopActionException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function recoveryAction(string $userIdentifier = null)
    {
        if (empty($userIdentifier)) {
            return $this->htmlResponse();
        }

        $email = $this->userRepository->findEmailByUsernameOrEmailOnPages(
            $userIdentifier,
            $this->getStorageFolders()
        );

        if ($email) {
            $this->recoveryService->sendRecoveryEmail($email);
        }

        if ($this->exposeNoneExistentUser($email)) {
            $this->addFlashMessage(
                $this->getTranslation('forgot_reset_message_error'),
                '',
                AbstractMessage::ERROR
            );
        } else {
            $this->addFlashMessage($this->getTranslation('forgot_reset_message_emailSent'));
        }

        $this->redirect('login', 'Login', 'felogin');
    }

    /**
     * Validate hash and make sure it's not expired. If it is not in the correct format or not set at all, a redirect
     * to recoveryAction() is made, without further information.
     */
    protected function validateIfHashHasExpired()
    {
        $hash = $this->request->hasArgument('hash') ? $this->request->getArgument('hash') : '';
        $hash = is_string($hash) ? $hash : '';

        if (!$this->hasValidHash($hash)) {
            $this->redirect('recovery', 'PasswordRecovery', 'felogin');
        }

        $timestamp = (int)GeneralUtility::trimExplode('|', $hash)[0];
        $currentTimestamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');

        // timestamp is expired or hash can not be assigned to a user
        if ($currentTimestamp > $timestamp || !$this->userRepository->existsUserWithHash(GeneralUtility::hmac($hash))) {
            $result = $this->request->getOriginalRequestMappingResults();
            $result->addError(new Error($this->getTranslation('change_password_notvalid_message'), 1554994253));
            $this->request->setOriginalRequestMappingResults($result);

            return (new ForwardResponse('recovery'))
                ->withControllerName('PasswordRecovery')
                ->withExtensionName('felogin')
                ->withArgumentsValidationResult($result);
        }
    }

    /**
     * Show the change password form if a valid hash is available.
     *
     * @param string $hash
     * @return ResponseInterface
     */
    public function showChangePasswordAction(string $hash = ''): ResponseInterface
    {
        // Validate the lifetime of the hash
        if (($response = $this->validateIfHashHasExpired()) instanceof ResponseInterface) {
            return $response;
        }

        $this->view->assign('hash', $hash);

        return $this->htmlResponse();
    }

    /**
     * Validate entered password and passwordRepeat values. If they are invalid a forward() to
     * showChangePasswordAction() takes place. All validation errors are put into the request mapping results.
     *
     * Used validators are configured via TypoScript settings.
     *
     * @throws NoSuchArgumentException
     * @throws StopActionException
     * @todo: Refactor all password checks to validators
     */
    public function validateHashAndPasswords()
    {
        // Validate the lifetime of the hash
        if (($response = $this->validateIfHashHasExpired()) instanceof ResponseInterface) {
            return $response;
        }

        // Exit early if newPass or newPassRepeat is not set.
        $originalResult = $this->request->getOriginalRequestMappingResults();
        $argumentsExist = $this->request->hasArgument('newPass') && $this->request->hasArgument('newPassRepeat');
        $argumentsEmpty = empty($this->request->getArgument('newPass')) || empty($this->request->getArgument('newPassRepeat'));

        if (!$argumentsExist || $argumentsEmpty) {
            $originalResult->addError(new Error(
                $this->getTranslation('empty_password_and_password_repeat'),
                1554971665
            ));
            $this->request->setOriginalRequestMappingResults($originalResult);

            return (new ForwardResponse('showChangePassword'))
                ->withControllerName('PasswordRecovery')
                ->withExtensionName('felogin')
                ->withArguments(['hash' => $this->request->getArgument('hash')])
                ->withArgumentsValidationResult($originalResult);
        }

        $this->validateNewPassword($originalResult);

        // todo: check if calling $this->errorAction is necessary here
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
     * @param string $newPass
     * @param string $hash
     *
     * @return ResponseInterface|string|ForwardResponse|null
     *
     * @throws StopActionException
     * @throws AspectNotFoundException
     * @throws InvalidPasswordHashException
     */
    public function changePasswordAction(string $newPass, string $hash)
    {
        if (($response = $this->validateHashAndPasswords()) instanceof ResponseInterface) {
            return $response;
        }

        $hashedPassword = GeneralUtility::makeInstance(PasswordHashFactory::class)
            ->getDefaultHashInstance('FE')
            ->getHashedPassword($newPass);

        if (($hashedPassword = $this->notifyPasswordChange(
            $newPass,
            $hashedPassword,
            $hash
        )) instanceof ForwardResponse) {
            return $hashedPassword;
        }

        $this->userRepository->updatePasswordAndInvalidateHash(GeneralUtility::hmac($hash), $hashedPassword);

        $this->addFlashMessage($this->getTranslation('change_password_done_message'));

        $this->redirect('login', 'Login', 'felogin');
    }

    /**
     * @param Result $originalResult
     *
     * @throws NoSuchArgumentException
     */
    protected function validateNewPassword(Result $originalResult): void
    {
        $newPass = $this->request->getArgument('newPass');

        // make sure the user entered the password twice
        if ($newPass !== $this->request->getArgument('newPassRepeat')) {
            $originalResult->addError(new Error($this->getTranslation('password_must_match_repeated'), 1554912163));
        }

        // Resolve validators from TypoScript configuration
        $validators = GeneralUtility::makeInstance(ValidatorResolverService::class)
            ->resolve($this->settings['passwordValidators']);

        // Call each validator on new password
        foreach ($validators ?? [] as $validator) {
            $result = $validator->validate($newPass);
            $originalResult->merge($result);
        }

        //set the result from all validators
        $this->request->setOriginalRequestMappingResults($originalResult);
    }

    /**
     * Wrapper to mock LocalizationUtility::translate
     *
     * @param string $key
     *
     * @return string
     */
    protected function getTranslation(string $key): string
    {
        return (string)LocalizationUtility::translate($key, 'felogin');
    }

    /**
     * Validates that $hash is in the expected format (timestamp|forgot_hash)
     *
     * @param string $hash
     *
     * @return bool
     */
    protected function hasValidHash($hash): bool
    {
        return !empty($hash) && is_string($hash) && strpos($hash, '|') === 10;
    }

    /**
     * @param string $newPassword Unencrypted new password
     * @param string $hashedPassword New password hash passed as reference
     * @param string $hash Forgot password hash
     * @return ForwardResponse|string
     */
    protected function notifyPasswordChange(string $newPassword, string $hashedPassword, string $hash)
    {
        $user = $this->userRepository->findOneByForgotPasswordHash(GeneralUtility::hmac($hash));
        if (is_array($user)) {
            $event = new PasswordChangeEvent($user, $hashedPassword, $newPassword);
            $this->eventDispatcher->dispatch($event);
            $hashedPassword = $event->getHashedPassword();
            if ($event->isPropagationStopped()) {
                $requestResult = $this->request->getOriginalRequestMappingResults();
                $requestResult->addError(new Error($event->getErrorMessage() ?? '', 1562846833));
                $this->request->setOriginalRequestMappingResults($requestResult);

                return (new ForwardResponse('showChangePassword'))
                    ->withControllerName('PasswordRecovery')
                    ->withExtensionName('felogin')
                    ->withArguments(['hash' => $hash]);
            }
        } else {
            // No user found
            $requestResult = $this->request->getOriginalRequestMappingResults();
            $requestResult->addError(new Error('Invalid hash', 1562846832));
            $this->request->setOriginalRequestMappingResults($requestResult);

            return (new ForwardResponse('showChangePassword'))
                ->withControllerName('PasswordRecovery')
                ->withExtensionName('felogin')
                ->withArguments(['hash' => $hash]);
        }

        return $hashedPassword;
    }

    /**
     * @param string|null $email
     * @return bool
     */
    protected function exposeNoneExistentUser(?string $email): bool
    {
        $acceptedValues = ['1', 1, 'true'];

        return !$email && in_array(
            $this->settings['exposeNonexistentUserInForgotPasswordDialog'] ?? null,
            $acceptedValues,
            true
        );
    }
}
