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

namespace TYPO3\CMS\Install\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Localization\JavaScriptLanguageDomainProvider as CoreJavaScriptLanguageDomainProvider;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\TranslationDomainMapper;

/**
 * @internal
 */
final readonly class JavaScriptLanguageDomainProvider implements MiddlewareInterface
{
    public function __construct(
        private LanguageServiceFactory $languageServiceFactory,
        private TranslationDomainMapper $translationDomainMapper,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = $request->getQueryParams()['install'] ?? [];
        if (!is_array($data)
            || ($data['action'] ?? null) !== 'labels'
            || !is_string(($data['domain'] ?? null))
        ) {
            return $handler->handle($request);
        }
        $domain = $data['domain'];
        $locale = 'en';

        // Only labels from core/backend/install are allowed to
        // disallow enumeration of available extensions
        $isValidDomain = (
            str_starts_with($domain, 'core.')
            || str_starts_with($domain, 'backend.')
            || str_starts_with($domain, 'install.')
        );
        if (!$isValidDomain) {
            return (new ResponseFactory())->createResponse(403);
        }

        $provider = new CoreJavaScriptLanguageDomainProvider(
            $this->languageServiceFactory,
            $this->translationDomainMapper,
            new ResponseFactory(),
            new StreamFactory(),
        );

        return $provider->createLanguageDomainResponse($domain, $locale);
    }
}
