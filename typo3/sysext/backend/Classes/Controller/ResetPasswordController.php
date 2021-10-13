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
use TYPO3\CMS\Backend\Authentication\PasswordReset;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\View\AuthenticationStyleInformation;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Controller responsible for rendering and processing password reset requests
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ResetPasswordController
{
    protected string $loginProvider = '';
    protected ?StandaloneView $view = null;
    protected ?ModuleTemplate $moduleTemplate = null;

    protected Context $context;
    protected Locales $locales;
    protected Features $features;
    protected UriBuilder $uriBuilder;
    protected PageRenderer $pageRenderer;
    protected PasswordReset $passwordReset;
    protected Typo3Information $typo3Information;
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected AuthenticationStyleInformation $authenticationStyleInformation;

    public function __construct(
        Context $context,
        Locales $locales,
        Features $features,
        UriBuilder $uriBuilder,
        PageRenderer $pageRenderer,
        PasswordReset $passwordReset,
        Typo3Information $typo3Information,
        ModuleTemplateFactory $moduleTemplateFactory,
        AuthenticationStyleInformation $authenticationStyleInformation
    ) {
        $this->context = $context;
        $this->locales = $locales;
        $this->features = $features;
        $this->uriBuilder = $uriBuilder;
        $this->pageRenderer = $pageRenderer;
        $this->passwordReset = $passwordReset;
        $this->typo3Information = $typo3Information;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->authenticationStyleInformation = $authenticationStyleInformation;
    }

    /**
     * Show a form to enter an email address to request a password reset email.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function forgetPasswordFormAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->initializeForgetPasswordView($request);
        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Validate the email address.
     *
     * Restricted to POST method in Configuration/Backend/Routes.php
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function initiatePasswordResetAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->initializeForgetPasswordView($request);
        $emailAddress = $request->getParsedBody()['email'] ?? '';
        $this->view->assign('email', $emailAddress);
        if (!GeneralUtility::validEmail($emailAddress)) {
            $this->view->assign('invalidEmail', true);
        } else {
            $this->passwordReset->initiateReset($request, $this->context, $emailAddress);
            $this->view->assign('resetInitiated', true);
        }
        $this->moduleTemplate->setContent($this->view->render());
        // Prevent time based information disclosure by waiting a random time
        // before sending a response. This prevents that the response time
        // can be an indicator if the used email exists or not. Wait a random
        // time between 200 milliseconds and 3 seconds.
        usleep(random_int(200000, 3000000));
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Validates the link and show a form to enter the new password.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function passwordResetAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->initializeResetPasswordView($request);
        if (!$this->passwordReset->isValidResetTokenFromRequest($request)) {
            $this->view->assign('invalidToken', true);
        }
        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Updates the password in the database.
     *
     * Restricted to POST method in Configuration/Backend/Routes.php
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function passwordResetFinishAction(ServerRequestInterface $request): ResponseInterface
    {
        // Token is invalid
        if (!$this->passwordReset->isValidResetTokenFromRequest($request)) {
            return $this->passwordResetAction($request);
        }
        $this->initializeResetPasswordView($request);
        if ($this->passwordReset->resetPassword($request, $this->context)) {
            $this->view->assign('resetExecuted', true);
        } else {
            $this->view->assign('error', true);
        }
        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    protected function initializeForgetPasswordView(ServerRequestInterface $request): void
    {
        $this->initialize($request);
        $this->view->setTemplate('Login/ForgetPasswordForm');
        $parameters = array_filter(['loginProvider' => $this->loginProvider]);
        $this->view->assignMultiple([
            'formUrl' => $this->uriBuilder->buildUriWithRedirect('password_forget_initiate_reset', $parameters, RouteRedirect::createFromRequest($request)),
            'returnUrl' => $this->uriBuilder->buildUriWithRedirect('login', $parameters, RouteRedirect::createFromRequest($request)),
        ]);
    }

    protected function initializeResetPasswordView(ServerRequestInterface $request): void
    {
        $this->initialize($request);
        $token = $request->getQueryParams()['t'] ?? '';
        $identity = $request->getQueryParams()['i'] ?? '';
        $expirationDate = $request->getQueryParams()['e'] ?? '';
        $parameters = array_filter(['loginProvider' => $this->loginProvider]);
        $formUrl = $this->uriBuilder->buildUriWithRedirect(
            'password_reset_finish',
            array_filter(array_merge($parameters, [
               't' => $token,
               'i' => $identity,
               'e' => $expirationDate,
            ])),
            RouteRedirect::createFromRequest($request)
        );
        $this->view->setTemplate('Login/ResetPasswordForm');
        $this->view->assignMultiple([
            'token' => $token,
            'identity' => $identity,
            'expirationDate' => $expirationDate,
            'formUrl' => $formUrl,
            'restartUrl' => $this->uriBuilder->buildUriWithRedirect('password_forget', $parameters, RouteRedirect::createFromRequest($request)),
        ]);
    }

    protected function initialize(ServerRequestInterface $request): void
    {
        // Only allow to execute this if not logged in as a user right now
        if ($this->context->getAspect('backend.user')->isLoggedIn()) {
            throw new PropagateResponseException(
                new RedirectResponse($this->uriBuilder->buildUriFromRoute('login'), 303),
                1618342858
            );
        }

        // Fetch login provider from the request
        $this->loginProvider = $request->getQueryParams()['loginProvider'] ?? '';

        // Try to get the preferred browser language
        $httpAcceptLanguage = $request->getServerParams()['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $preferredBrowserLanguage = $this->locales->getPreferredClientLanguage($httpAcceptLanguage);

        // If we found a $preferredBrowserLanguage and it is not the default language
        // initialize $this->getLanguageService() again with $preferredBrowserLanguage
        if ($preferredBrowserLanguage !== 'default') {
            $this->getLanguageService()->init($preferredBrowserLanguage);
            $this->pageRenderer->setLanguage($preferredBrowserLanguage);
        }

        $this->getLanguageService()->includeLLFile('EXT:backend/Resources/Private/Language/locallang_login.xlf');

        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->moduleTemplate->setTitle('TYPO3 CMS Login: ' . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? ''));

        $this->view = $this->moduleTemplate->getView();
        $this->view->getRequest()->setControllerExtensionName('Backend');
        $this->view->assignMultiple([
            'enablePasswordReset' => $this->passwordReset->isEnabled(),
            'referrerCheckEnabled' => $this->features->isFeatureEnabled('security.backend.enforceReferrer'),
            'loginUrl' => (string)$request->getUri(),
        ]);

        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Login');
        $this->provideCustomLoginStyling();
    }

    protected function provideCustomLoginStyling(): void
    {
        if (($backgroundImageStyles = $this->authenticationStyleInformation->getBackgroundImageStyles()) !== '') {
            $this->pageRenderer->addCssInlineBlock('loginBackgroundImage', $backgroundImageStyles);
        }
        if (($footerNote = $this->authenticationStyleInformation->getFooterNote()) !== '') {
            $this->view->assign('loginFootnote', $footerNote);
        }
        if (($highlightColorStyles = $this->authenticationStyleInformation->getHighlightColorStyles()) !== '') {
            $this->pageRenderer->addCssInlineBlock('loginHighlightColor', $highlightColorStyles);
        }
        if (($logo = $this->authenticationStyleInformation->getLogo()) !== '') {
            $logoAlt = $this->authenticationStyleInformation->getLogoAlt();
        } else {
            $logo = $this->authenticationStyleInformation->getDefaultLogo();
            $logoAlt = $this->getLanguageService()->getLL('typo3.altText');
            $this->pageRenderer->addCssInlineBlock('loginLogo', $this->authenticationStyleInformation->getDefaultLogoStyles());
        }
        $this->view->assignMultiple([
            'logo' => $logo,
            'logoAlt' => $logoAlt,
            'images' => $this->authenticationStyleInformation->getSupportingImages(),
            'copyright' => $this->typo3Information->getCopyrightNotice(),
        ]);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
