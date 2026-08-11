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

namespace TYPO3\CMS\Core\Http\Security;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Evaluates the `Referer` header of a request against the application the request was addressed to.
 *
 * Deciding whether a referrer is same-origin (= originating from the very same application) cannot
 * be done generically - the concrete URI details of the addressed application are required for that.
 * Therefore, this class is abstract and each application (backend, install tool) has to provide its
 * own `resolveReferrerType()` implementation.
 *
 * @internal
 */
abstract class ReferrerEnforcer
{
    protected const TYPE_REFERRER_EMPTY = 1;
    protected const TYPE_REFERRER_SAME_SITE = 2;
    protected const TYPE_REFERRER_SAME_ORIGIN = 4;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var string
     */
    protected $requestHost;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
        $this->requestHost = rtrim($this->resolveRequestHost($request), '/') . '/';
    }

    public function handle(?array $options = null): ?ResponseInterface
    {
        $referrerType = $this->resolveReferrerType();
        // valid referrer, no more actions required
        if ($referrerType & self::TYPE_REFERRER_SAME_ORIGIN) {
            return null;
        }
        $flags = $options['flags'] ?? [];
        $expiration = $options['expiration'] ?? 5;
        $nonce = $this->request->getAttribute('nonce');
        // referrer is missing and route requested to refresh
        // (created HTML refresh to enforce having referrer)
        if (($this->request->getQueryParams()['referrer-refresh'] ?? 0) <= time()
            && (
                in_array('refresh-always', $flags, true)
                || ($referrerType & self::TYPE_REFERRER_EMPTY && in_array('refresh-empty', $flags, true))
                || ($referrerType & self::TYPE_REFERRER_SAME_SITE && in_array('refresh-same-site', $flags, true))
            )
        ) {
            $refreshUri = $this->request->getUri();
            parse_str($refreshUri->getQuery(), $queryParams);
            $queryParams['referrer-refresh'] = time() + $expiration;
            $refreshUri = $refreshUri->withQuery(
                http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986)
            );
            $scriptUri = $this->resolveAbsoluteWebPath(
                'EXT:core/Resources/Public/JavaScript/referrer-refresh.js'
            );
            $attributes = ['src' => $scriptUri];
            if ($nonce instanceof ConsumableNonce) {
                $attributes['nonce'] = $nonce->consumeStatic(Directive::ScriptSrcElem);
            }
            // simulating navigate event by clicking anchor link
            // since meta-refresh won't change `document.referrer` in e.g. Firefox
            return new HtmlResponse(sprintf(
                '<html>'
                . '<head><link rel="icon" href="data:image/svg+xml,"></head>'
                . '<body><a href="%s" id="referrer-refresh">&nbsp;</a>'
                . '<script %s></script></body>'
                . '</html>',
                htmlspecialchars((string)$refreshUri),
                GeneralUtility::implodeAttributes($attributes, true)
            ));
        }
        $subject = $options['subject'] ?? '';
        if ($referrerType & self::TYPE_REFERRER_EMPTY) {
            // still empty referrer or invalid referrer, deny route invocation
            throw new MissingReferrerException(
                sprintf('Missing referrer%s', $subject !== '' ? ' for ' . $subject : ''),
                1588095935
            );
        }
        // referrer is given, but does not match current base URL
        throw new InvalidReferrerException(
            sprintf('Invalid referrer%s', $subject !== '' ? ' for ' . $subject : ''),
            1588095936
        );
    }

    protected function resolveAbsoluteWebPath(string $target): string
    {
        return PathUtility::getPublicResourceWebPath($target);
    }

    /**
     * Determines whether the referrer is same-origin (= the very same application), same-site
     * (= the same host, but a different application) or neither of both.
     *
     * Implementations must not fall back to the request directory to detect same-origin: all
     * applications are served from the same entry script, which would make any same-site referrer
     * appear as same-origin.
     */
    abstract protected function resolveReferrerType(): int;

    protected function resolveRequestHost(ServerRequestInterface $request): string
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        if ($normalizedParams instanceof NormalizedParams) {
            return $normalizedParams->getRequestHost();
        }
        return GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
    }
}
