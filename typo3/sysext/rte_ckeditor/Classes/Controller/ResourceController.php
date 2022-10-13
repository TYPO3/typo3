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

namespace TYPO3\CMS\RteCKEditor\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ScssPhp\ScssPhp\Compiler;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This API is used internally only.
 * @todo Add this to core? Or keep it here?
 */
class ResourceController
{
    public function stylesheetAction(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'GET') {
            return (new NullResponse())->withStatus(404);
        }
        $queryParams = $request->getQueryParams();
        $params = (string)($queryParams['params'] ?? '');
        $hmac = (string)($queryParams['hmac'] ?? '');
        if ($hmac !== $this->hmac($params, 'stylesheet')) {
            return (new NullResponse())->withStatus(400);
        }
        // @todo additional checks whether file is local, not remote...

        $styleSrcParams = json_decode($queryParams['params'] ?? '', true);
        $styleSrc = (string)($styleSrcParams['styleSrc'] ?? '');
        $cssPrefix = (string)($styleSrcParams['cssPrefix'] ?? '');
        $styleSrcPath = Environment::getPublicPath() . $styleSrc;
        $styleSrcContent = file_get_contents($styleSrcPath);
        $styleSrcHash = sha1(json_encode([$cssPrefix, $styleSrcContent]));

        $cacheIdentifier = 'rte-resource-stylesheet%' . $styleSrcHash;
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('assets');

        if ($cache->has($cacheIdentifier)) {
            $compiledStyles = $cache->get($cacheIdentifier);
        } else {
            if (trim($cssPrefix) !== '') {
                $source = $this->prepareCssForScssParsing($cssPrefix, $styleSrcContent);
                $compiledStyles = (new Compiler())->compileString($source)->getCss();
            } else {
                $compiledStyles = $styleSrcContent;
            }
            $cache->set($cacheIdentifier, $compiledStyles);
        }

        $stylesStream = new Stream('php://temp', 'w');
        $stylesStream->write($compiledStyles);
        // @todo consider sending cache/expiration headers to browser
        return new Response($stylesStream, 200, ['Content-Type' => 'text/css']);
    }

    // this is shit and needs to be a general token/payload component, having signatures (JWT?)
    public function hmac(string $payload, string $scope): string
    {
        return GeneralUtility::hmac($payload, self::class . '::' . $scope);
    }

    private function prepareCssForScssParsing(string $prefix, string $content): string
    {
        // Some CSS minifier remove the semicolon before the curly brace
        // While this is valid CSS, the ScssPHP Parser is unable to identify
        // the end of a declaration block. We are adding them, to avoid
        // parsing errors. Superfluous semicolons will be dropped by the parser.
        $content = str_replace('}', ';}', $content);

        // Ensure the additional CSS is only applied to the Editor by
        // prefixing all CSS definitions.
        $content = sprintf("%s {\n%s\n}", $prefix, $content);

        // Moving CSS variables assigned to :root to the new parent.
        $content = str_replace(':root', '&', $content);

        return $content;
    }
}
