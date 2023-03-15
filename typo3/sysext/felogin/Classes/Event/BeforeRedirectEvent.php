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

namespace TYPO3\CMS\FrontendLogin\Event;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Notification before a redirect is made, which also allows to modify
 * the actual redirect URL. Setting the redirect to an empty string
 * will avoid triggering a redirect.
 */
final class BeforeRedirectEvent
{
    /**
     * @var string
     */
    private $loginType;

    /**
     * @var string
     */
    private $redirectUrl;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    public function __construct(string $loginType, string $redirectUrl, $request)
    {
        $this->loginType = $loginType;
        $this->redirectUrl = $redirectUrl;
        $this->request = $request;
    }

    public function getLoginType(): string
    {
        return $this->loginType;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function setRedirectUrl(string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
