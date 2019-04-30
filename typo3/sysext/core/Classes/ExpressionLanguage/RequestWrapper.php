<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\ExpressionLanguage;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\RouteResultInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Class RequestWrapper
 * This class provides access to some methods of the ServerRequest object.
 * To prevent access to all methods of the ServerRequest object within conditions,
 * this class was introduced to control which methods are exposed.
 *
 * Additionally this class can be used to simulate a request for condition matching in case the condition matcher calls
 * should be simulated (for example simulating parsing of TypoScript on CLI)
 * @internal
 */
class RequestWrapper
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

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

    public function getSite(): ?SiteInterface
    {
        return $this->request->getAttribute('site');
    }

    public function getSiteLanguage(): ?SiteLanguage
    {
        return $this->request->getAttribute('language');
    }

    public function getNormalizedParams(): ?NormalizedParams
    {
        return $this->request->getAttribute('normalizedParams');
    }

    public function getPageArguments(): ?RouteResultInterface
    {
        return $this->request->getAttribute('routing');
    }
}
