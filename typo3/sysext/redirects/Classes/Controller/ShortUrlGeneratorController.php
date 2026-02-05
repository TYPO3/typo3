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

namespace TYPO3\CMS\Redirects\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Redirects\Service\ShortUrlService;

/**
 * @internal Only to be used within TYPO3. Might change in the future.
 */
#[Autoconfigure(public: true)]
readonly class ShortUrlGeneratorController
{
    public function __construct(
        protected ShortUrlService $shortUrlService,
        protected ResponseFactoryInterface $responseFactory,
        protected StreamFactoryInterface $streamFactory,
    ) {}

    public function generate(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $sourceHost = (string)($parsedBody['source_host'] ?? '');

        $shortUrl = $this->shortUrlService->generateUniqueShortUrlPath($sourceHost);
        if ($shortUrl === null) {
            return $this->createResponse([
                'success' => false,
                'message' => 'Could not generate a unique short URL after multiple attempts.',
            ]);
        }

        return $this->createResponse([
            'success' => true,
            'shortUrl' => $shortUrl,
        ]);
    }

    public function validate(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $sourceHost = (string)($parsedBody['source_host'] ?? '');
        $sourcePath = (string)($parsedBody['source_path'] ?? '');

        if ($sourcePath === '') {
            return $this->createResponse(['isUnique' => true]);
        }

        // Ensure leading slash
        if ($sourcePath[0] !== '/') {
            $sourcePath = '/' . $sourcePath;
        }

        $isUnique = $this->shortUrlService->isUniqueShortUrl($sourceHost, $sourcePath);
        $response = ['isUnique' => $isUnique];
        if (!$isUnique) {
            $response['message'] = $this->getLanguageService()
                ->sL('redirects.modules.short_urls:validation.duplicate_short_url');
        }
        return $this->createResponse($response);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function createResponse(array $data): ResponseInterface
    {
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream((string)json_encode($data)));
    }
}
