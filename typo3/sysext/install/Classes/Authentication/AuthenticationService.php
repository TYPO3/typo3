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

namespace TYPO3\CMS\Install\Authentication;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Mime\RawMessage;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\Install\Service\SessionService;

/**
 * Authenticates a user (currently comparing it through the install tool password, but could be extended)
 * @internal only to be used within EXT:install
 */
class AuthenticationService
{
    /**
     * @var SessionService
     */
    protected $sessionService;

    /**
     * @var TemplatePaths
     */
    protected $templatePaths;

    /**
     * @param SessionService $sessionService
     */
    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
        $templateConfiguration = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];
        $templateConfiguration['templateRootPaths'][20] = 'EXT:install/Resources/Private/Templates/Email/';
        $this->templatePaths = new TemplatePaths($templateConfiguration);
    }

    /**
     * Checks against a given password
     *
     * @param string|null $password
     * @param ServerRequestInterface $request
     * @return bool if authentication was successful, otherwise false
     */
    public function loginWithPassword($password, ServerRequestInterface $request): bool
    {
        $validPassword = false;
        if ($password !== null && $password !== '') {
            $installToolPassword = $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'];
            $hashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
            // Throws an InvalidPasswordHashException if no hash mechanism for the stored password is found
            $hashInstance = $hashFactory->get($installToolPassword, 'BE');
            // @todo: This code should check required hash updates and update the hash if needed
            $validPassword = $hashInstance->checkPassword($password, $installToolPassword);
        }
        if ($validPassword) {
            $this->sessionService->setAuthorized();
            $this->sendLoginSuccessfulMail($request);
            return true;
        }
        $this->sendLoginFailedMail($request);
        return false;
    }

    /**
     * If install tool login mail is set, send a mail for a successful login.
     *
     * @param ServerRequestInterface $request
     */
    protected function sendLoginSuccessfulMail(ServerRequestInterface $request)
    {
        $warningEmailAddress = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
        if (!$warningEmailAddress) {
            return;
        }
        $email = GeneralUtility::makeInstance(FluidEmail::class, $this->templatePaths);
        $email
            ->to($warningEmailAddress)
            ->subject('Install Tool Login at \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\'')
            ->from(new Address($this->getSenderEmailAddress(), $this->getSenderEmailName()))
            ->setTemplate('Security/InstallToolLogin')
            ->setRequest($request);
        $this->sendEmail($email);
    }

    /**
     * If install tool login mail is set, send a mail for a failed login.
     *
     * @param ServerRequestInterface $request
     */
    protected function sendLoginFailedMail(ServerRequestInterface $request)
    {
        $warningEmailAddress = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
        if (!$warningEmailAddress) {
            return;
        }
        $formValues = GeneralUtility::_GP('install');
        $email = GeneralUtility::makeInstance(FluidEmail::class, $this->templatePaths);
        $email
            ->to($warningEmailAddress)
            ->subject('Install Tool Login ATTEMPT at \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\'')
            ->from(new Address($this->getSenderEmailAddress(), $this->getSenderEmailName()))
            ->setTemplate('Security/InstallToolLoginAttempt')
            ->assign('lastCharactersOfPassword', substr(md5($formValues['password']), -5))
            ->setRequest($request);
        $this->sendEmail($email);
    }

    /**
     * Sends an email and gracefully logs if the mail could not be sent due to configuration errors.
     *
     * @param RawMessage $email
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    protected function sendEmail(RawMessage $email): void
    {
        try {
            GeneralUtility::makeInstance(Mailer::class)->send($email);
        } catch (TransportException $e) {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->warning('Could not send notification email to ' . $this->getSenderEmailAddress() . ' due to mailer settings error', [
                'recipientList' => $this->getSenderEmailAddress(),
                'exception' => $e,
            ]);
        } catch (RfcComplianceException $e) {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->warning('Could not send notification email to ' . $this->getSenderEmailAddress() . ' due to invalid email address', [
                'recipientList' => $this->getSenderEmailAddress(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Get sender address from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
     * If this setting is empty fall back to 'no-reply@example.com'
     *
     * @return string Returns an email address
     */
    protected function getSenderEmailAddress()
    {
        return MailUtility::getSystemFromAddress();
    }

    /**
     * Gets sender name from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
     * If this setting is empty, it falls back to a default string.
     *
     * @return string
     */
    protected function getSenderEmailName()
    {
        return MailUtility::getSystemFromName() ?: 'TYPO3 CMS install tool';
    }
}
