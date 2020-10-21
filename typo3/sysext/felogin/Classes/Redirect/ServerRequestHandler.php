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

namespace TYPO3\CMS\FrontendLogin\Redirect;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\FrontendLogin\Validation\RedirectUrlValidator;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class ServerRequestHandler
{
    /**
     * @var RedirectUrlValidator
     */
    protected $redirectUrlValidator;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    public function __construct()
    {
        // todo: refactor when extbase handles PSR-15 requests
        $this->request = $GLOBALS['TYPO3_REQUEST'];
        $this->redirectUrlValidator = GeneralUtility::makeInstance(
            RedirectUrlValidator::class,
            GeneralUtility::makeInstance(SiteFinder::class)
        );
    }

    /**
     * Returns a property that exists in post or get context
     *
     * @param string $propertyName
     * @return mixed|null
     */
    public function getPropertyFromGetAndPost(string $propertyName)
    {
        return $this->request->getParsedBody()[$propertyName] ?? $this->request->getQueryParams(
            )[$propertyName] ?? null;
    }

    /**
     * Returns validated redirect url contained in request param return_url or redirect_url
     *
     * @return string
     */
    public function getRedirectUrlRequestParam(): string
    {
        // If config.typolinkLinkAccessRestrictedPages is set, the var is return_url
        $redirectUrl = (string)$this->getPropertyFromGetAndPost('return_url')
            ?: (string)$this->getPropertyFromGetAndPost('redirect_url');

        return $this->redirectUrlValidator->isValid($redirectUrl) ? $redirectUrl : '';
    }
}
