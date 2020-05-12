<?php

declare(strict_types = 1);

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @internal
 */
class ReferrerEnforcer
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var string
     */
    protected $requestDir;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
        $this->requestDir = $this->resolveRequestDir($request);
    }

    public function handle(array $options = null): ?ResponseInterface
    {
        $referrer = $this->request->getServerParams()['HTTP_REFERER'] ?? '';
        // valid referrer, no more actions required
        if ($referrer !== '' && strpos($referrer, $this->requestDir) === 0) {
            return null;
        }
        $flags = $options['flags'] ?? [];
        $expiration = $options['expiration'] ?? 5;
        // referrer is missing and route requested to refresh
        // (created HTML refresh to enforce having referrer)
        if (($this->request->getQueryParams()['referrer-refresh'] ?? 0) <= time()
            && $referrer === '' && in_array('refresh-empty', $flags, true)) {
            $refreshUri = $this->request->getUri();
            $refreshUri = $refreshUri->withQuery(
                $refreshUri->getQuery() . '&referrer-refresh=' . (time() + $expiration)
            );
            $scriptUri = PathUtility::getAbsoluteWebPath(
                GeneralUtility::getFileAbsFileName('EXT:core/Resources/Public/JavaScript/ReferrerRefresh.js')
            );
            return new HtmlResponse(sprintf(
                '<html>'
                . '<head><link rel="icon" href="data:image/svg+xml,"></head>'
                . '<body><a href="%1$s" id="referrer-refresh">&nbsp;</a>'
                . '<script src="%2$s"></script></body>'
                . '</html>',
                htmlspecialchars((string)$refreshUri),
                htmlspecialchars($scriptUri)
            ));
        }
        $subject = $options['subject'] ?? '';
        if ($referrer === '') {
            // still empty referrer or invalid referrer, deny route invocation
            throw new MissingReferrerException(
                sprintf('Missing referrer%s', $subject !== '' ? ' for ' . $subject : ''),
                1588095935
            );
        }
        // referrer is given, but does not match current base URL
        throw new InvalidReferrerException(
            sprintf('Missing referrer%s', $subject !== '' ? ' for ' . $subject : ''),
            1588095936
        );
    }

    protected function resolveRequestDir(ServerRequestInterface $request): string
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        if ($normalizedParams instanceof NormalizedParams) {
            return $normalizedParams->getRequestDir();
        }
        return GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR');
    }
}
