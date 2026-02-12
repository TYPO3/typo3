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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\RouteResult;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\TranslationDomainMapper;

/**
 * @internal
 */
#[AsController]
final readonly class JavaScriptLanguageDomainController
{
    public function __construct(
        private LanguageServiceFactory $languageServiceFactory,
        private TranslationDomainMapper $translationDomainMapper,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function getLanguageDomainAction(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RouteResult $routing */
        $routing = $request->getAttribute('routing');
        $domain = $routing['domain'];
        $locale = $routing['locale'];
        $languageService = $this->languageServiceFactory->create($locale);
        $error = '';
        $allLabels = [];

        if (!$this->translationDomainMapper->isValidDomainName($domain)) {
            // @todo check for unavailable domain name and deprecation state
            $error = 'throw new Error("Invalid domain name");';
        } else {
            $allLabels = $languageService->getLabelsFromResource($domain);
        }

        $javaScriptModuleContents = implode("\n", [
            'import { LabelProvider } from "@typo3/backend/localization/label-provider.js";',
            $error,
            'export default new LabelProvider(',
            '    ' . json_encode((object)$allLabels),
            ');',
        ]);

        $lifetime = 3600 * 24 * 365;
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/javascript')
            ->withHeader('Content-Length', (string)strlen($javaScriptModuleContents))
            ->withHeader('Expires', gmdate('D, d M Y H:i:s T', (min($GLOBALS['EXEC_TIME'] + $lifetime, PHP_INT_MAX))))
            ->withHeader('Cache-Control', 'private, max-age=' . $lifetime)
            ->withBody(
                $this->streamFactory->createStream($javaScriptModuleContents)
            );
    }
}
