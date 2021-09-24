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

namespace TYPO3\CMS\Core\Authentication\Mfa\Provider;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaViewType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * MFA provider for time-based one-time password authentication
 *
 * @internal should only be used by the TYPO3 Core
 */
class TotpProvider implements MfaProviderInterface
{
    private const MAX_ATTEMPTS = 3;

    protected Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Check if a TOTP is given in the current request
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function canProcess(ServerRequestInterface $request): bool
    {
        return $this->getTotp($request) !== '';
    }

    /**
     * Evaluate if the provider is activated by checking the
     * active state and the secret from the provider properties.
     *
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function isActive(MfaProviderPropertyManager $propertyManager): bool
    {
        return (bool)$propertyManager->getProperty('active')
            && $propertyManager->getProperty('secret', '') !== '';
    }

    /**
     * Evaluate if the provider is temporarily locked by checking
     * the current attempts state from the provider properties.
     *
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function isLocked(MfaProviderPropertyManager $propertyManager): bool
    {
        $attempts = (int)$propertyManager->getProperty('attempts', 0);

        // Assume the provider is locked in case the maximum attempts are exceeded.
        // A provider however can only be locked if set up - an entry exists in database.
        return $propertyManager->hasProviderEntry() && $attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Verify the given TOTP and update the provider properties in case the TOTP is valid.
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function verify(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        if (!$this->isActive($propertyManager) || $this->isLocked($propertyManager)) {
            // Can not verify an inactive or locked provider
            return false;
        }

        $totp = $this->getTotp($request);
        $secret = $propertyManager->getProperty('secret', '');
        $verified = GeneralUtility::makeInstance(Totp::class, $secret)->verifyTotp($totp, 2);
        if (!$verified) {
            $attempts = $propertyManager->getProperty('attempts', 0);
            $propertyManager->updateProperties(['attempts' => ++$attempts]);
            return false;
        }
        $propertyManager->updateProperties([
            'attempts' => 0,
            'lastUsed' => $this->context->getPropertyFromAspect('date', 'timestamp'),
        ]);
        return true;
    }

    /**
     * Activate the provider by checking the necessary parameters,
     * verifying the TOTP and storing the provider properties.
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

        if (!$this->canProcess($request)) {
            // Return since the request can not be processed by this provider
            return false;
        }

        $secret = (string)($request->getParsedBody()['secret'] ?? '');
        $checksum = (string)($request->getParsedBody()['checksum'] ?? '');
        if ($secret === '' || !hash_equals(GeneralUtility::hmac($secret, 'totp-setup'), $checksum)) {
            // Return since the request does not contain the initially created secret
            return false;
        }

        $totpInstance = GeneralUtility::makeInstance(Totp::class, $secret);
        if (!$totpInstance->verifyTotp($this->getTotp($request), 2)) {
            // Return since the given TOTP could not be verified
            return false;
        }

        // If valid, prepare the provider properties to be stored
        $properties = ['secret' => $secret, 'active' => true];
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
     * Handle the save action by updating the provider properties
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function update(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        if (!$this->isActive($propertyManager) || $this->isLocked($propertyManager)) {
            // Can not update an inactive or locked provider
            return false;
        }

        $name = (string)($request->getParsedBody()['name'] ?? '');
        if ($name !== '') {
            return $propertyManager->updateProperties(['name' => $name]);
        }

        // Provider properties successfully updated
        return true;
    }

    /**
     * Handle the unlock action by resetting the attempts provider property
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

        // Reset the attempts
        return $propertyManager->updateProperties(['attempts' => 0]);
    }

    /**
     * Handle the deactivate action. For security reasons, the provider entry
     * is completely deleted and setting up this provider again, will therefore
     * create a brand new entry.
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function deactivate(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        if (!$this->isActive($propertyManager)) {
            // Can not deactivate an inactive provider
            return false;
        }

        // Delete the provider entry
        return $propertyManager->deleteProviderEntry();
    }

    /**
     * Initialize view and forward to the appropriate implementation
     * based on the view type to be returned.
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @param string $type
     * @return ResponseInterface
     */
    public function handleRequest(
        ServerRequestInterface $request,
        MfaProviderPropertyManager $propertyManager,
        string $type
    ): ResponseInterface {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths(['EXT:core/Resources/Private/Templates/Authentication/MfaProvider/Totp']);
        switch ($type) {
            case MfaViewType::SETUP:
                $this->prepareSetupView($view, $propertyManager);
                break;
            case MfaViewType::EDIT:
                $this->prepareEditView($view, $propertyManager);
                break;
            case MfaViewType::AUTH:
                $this->prepareAuthView($view, $propertyManager);
                break;
        }
        return new HtmlResponse($view->assign('providerIdentifier', $propertyManager->getIdentifier())->render());
    }

    /**
     * Generate a new shared secret, generate the otpauth URL and create a qr-code
     * for improved usability. Set template and assign necessary variables for the
     * setup view.
     */
    protected function prepareSetupView(StandaloneView $view, MfaProviderPropertyManager $propertyManager): void
    {
        $userData = $propertyManager->getUser()->user ?? [];
        $secret = Totp::generateEncodedSecret([(string)($userData['uid'] ?? ''), (string)($userData['username'] ?? '')]);
        $totpInstance = GeneralUtility::makeInstance(Totp::class, $secret);
        $totpAuthUrl = $totpInstance->getTotpAuthUrl(
            (string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? 'TYPO3'),
            (string)($userData['email'] ?? '') ?: (string)($userData['username'] ?? '')
        );
        $view->setTemplate('Setup');
        $view->assignMultiple([
            'secret' => $secret,
            'totpAuthUrl' => $totpAuthUrl,
            'qrCode' => $this->getSvgQrCode($totpAuthUrl),
            // Generate hmac of the secret to prevent it from being changed in the setup from
            'checksum' => GeneralUtility::hmac($secret, 'totp-setup'),
        ]);
    }

    /**
     * Set the template and assign necessary variables for the edit view
     */
    protected function prepareEditView(StandaloneView $view, MfaProviderPropertyManager $propertyManager): void
    {
        $view->setTemplate('Edit');
        $view->assignMultiple([
            'name' => $propertyManager->getProperty('name'),
            'lastUsed' => $this->getDateTime($propertyManager->getProperty('lastUsed', 0)),
            'updated' => $this->getDateTime($propertyManager->getProperty('updated', 0)),
        ]);
    }

    /**
     * Set the template for the auth view where the user has to provide the TOTP
     */
    protected function prepareAuthView(StandaloneView $view, MfaProviderPropertyManager $propertyManager): void
    {
        $view->setTemplate('Auth');
        $view->assign('isLocked', $this->isLocked($propertyManager));
    }

    /**
     * Internal helper method for fetching the TOTP from the request
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function getTotp(ServerRequestInterface $request): string
    {
        return trim((string)($request->getQueryParams()['totp'] ?? $request->getParsedBody()['totp'] ?? ''));
    }

    /**
     * Internal helper method for generating a svg QR-code for TOTP applications
     *
     * @param string $content
     * @return string
     */
    protected function getSvgQrCode(string $content): string
    {
        $qrCodeRenderer = new ImageRenderer(
            new RendererStyle(225, 4),
            new SvgImageBackEnd()
        );

        return (new Writer($qrCodeRenderer))->writeString($content);
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
}
