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

namespace TYPO3\CMS\Core\ExpressionLanguage;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Some scopes - especially fetching page TSconfig do not have a Request, for instance
 * when the DataHandler needs page TSconfig of a page. As such, using conditions based
 * on Request data is problematic for user TSconfig and page TSconfig.
 *
 * This was working in v11, though. To deprecate this case, we provide the 'request'
 * variable through this DeprecatingRequestWrapper and log usages.
 *
 * @internal
 * @deprecated since v12, will be removed in v13. Remove in UserTsConfigFactory and PageTsConfigFactory as well!
 */
class DeprecatingRequestWrapper extends RequestWrapper
{
    protected ServerRequestInterface $request;

    public function __construct(?ServerRequestInterface $request)
    {
        $this->request = $request ?? new ServerRequest();
    }

    public function getQueryParams(): array
    {
        trigger_error(
            'Using conditions based on request data within page TScnfig or user TSconfig has been deprecated' .
            ' and will stop working with TYPO3 v13. Switch to a different condition instead, for instance ' .
            ' based on the backend user object.',
            E_USER_DEPRECATED
        );
        return $this->request->getQueryParams();
    }

    public function getParsedBody(): array
    {
        trigger_error(
            'Using conditions based on request data within page TScnfig or user TScnfig has been deprecated' .
            ' and will stop working with TYPO3 v13. Switch to a different condition instead, for instance ' .
            ' based on the backend user object.',
            E_USER_DEPRECATED
        );
        return (array)($this->request->getParsedBody() ?? []);
    }

    public function getHeaders(): array
    {
        trigger_error(
            'Using conditions based on request data within page TSconfig or user TSconfig has been deprecated' .
            ' and will stop working with TYPO3 v13. Switch to a different condition instead, for instance ' .
            ' based on the backend user object.',
            E_USER_DEPRECATED
        );
        return $this->request->getHeaders();
    }

    public function getCookieParams(): array
    {
        trigger_error(
            'Using conditions based on request data within page TSconfig or user TSconfig has been deprecated' .
            ' and will stop working with TYPO3 v13. Switch to a different condition instead, for instance ' .
            ' based on the backend user object.',
            E_USER_DEPRECATED
        );
        return $this->request->getCookieParams();
    }

    /**
     * @todo: Could be removed since 'site' variable is provided explicitly.
     */
    public function getSite(): ?SiteInterface
    {
        trigger_error(
            'Using conditions based on request data within page TSconfig or user TSconfig has been deprecated' .
            ' and will stop working with TYPO3 v13. Switch to a different condition instead, for instance ' .
            ' based on the backend user object.',
            E_USER_DEPRECATED
        );
        return $this->request->getAttribute('site');
    }

    /**
     * @todo: Could be removed since 'siteLanguage' variable is provided explicitly.
     */
    public function getSiteLanguage(): ?SiteLanguage
    {
        trigger_error(
            'Using conditions based on request data within page TSconfig or user TSconfig has been deprecated' .
            ' and will stop working with TYPO3 v13. Switch to a different condition instead, for instance ' .
            ' based on the backend user object.',
            E_USER_DEPRECATED
        );
        return $this->request->getAttribute('language');
    }

    public function getNormalizedParams(): ?NormalizedParams
    {
        trigger_error(
            'Using conditions based on request data within page TSconfig or user TSconfig has been deprecated' .
            ' and will stop working with TYPO3 v13. Switch to a different condition instead, for instance ' .
            ' based on the backend user object.',
            E_USER_DEPRECATED
        );
        return $this->request->getAttribute('normalizedParams');
    }

    public function getPageArguments(): ?PageArguments
    {
        trigger_error(
            'Using conditions based on request data within page TSconfig or user TSconfig has been deprecated' .
            ' and will stop working with TYPO3 v13. Switch to a different condition instead, for instance ' .
            ' based on the backend user object.',
            E_USER_DEPRECATED
        );
        return ($routing = $this->request->getAttribute('routing')) instanceof PageArguments ? $routing : null;
    }
}
