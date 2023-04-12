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
 * This class provides access to some methods of the ServerRequest object.
 * To prevent access to all methods of the ServerRequest object within conditions,
 * this class was introduced to control which methods are exposed.
 *
 * @internal
 */
class RequestWrapper
{
    protected ServerRequestInterface $request;

    public function __construct(?ServerRequestInterface $request)
    {
        $this->request = $request ?? new ServerRequest();
    }

    public function getQueryParams(): array
    {
        return $this->request->getQueryParams();
    }

    public function getParsedBody(): array
    {
        return (array)($this->request->getParsedBody() ?? []);
    }

    public function getHeaders(): array
    {
        return $this->request->getHeaders();
    }

    public function getCookieParams(): array
    {
        return $this->request->getCookieParams();
    }

    /**
     * @todo: Could be removed since 'site' variable is provided explicitly.
     */
    public function getSite(): ?SiteInterface
    {
        return $this->request->getAttribute('site');
    }

    /**
     * @todo: Could be removed since 'siteLanguage' variable is provided explicitly.
     */
    public function getSiteLanguage(): ?SiteLanguage
    {
        return $this->request->getAttribute('language');
    }

    public function getNormalizedParams(): ?NormalizedParams
    {
        return $this->request->getAttribute('normalizedParams');
    }

    public function getPageArguments(): ?PageArguments
    {
        return ($routing = $this->request->getAttribute('routing')) instanceof PageArguments ? $routing : null;
    }
}
