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

namespace TYPO3\CMS\Backend\Http\Security;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Security\ReferrerEnforcer as CoreReferrerEnforcer;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Treats a referrer as same-origin only if it points into the backend entry point
 * (e.g. `https://example.org/typo3/`) - and not merely to the same host.
 *
 * @internal
 */
class ReferrerEnforcer extends CoreReferrerEnforcer
{
    protected BackendEntryPointResolver $backendEntryPointResolver;

    public function __construct(
        ServerRequestInterface $request,
        ?BackendEntryPointResolver $backendEntryPointResolver = null
    ) {
        parent::__construct($request);
        $this->backendEntryPointResolver = $backendEntryPointResolver
            ?? GeneralUtility::makeInstance(BackendEntryPointResolver::class);
    }

    protected function resolveReferrerType(): int
    {
        $referrer = $this->request->getServerParams()['HTTP_REFERER'] ?? '';
        if ($referrer === '') {
            return self::TYPE_REFERRER_EMPTY;
        }

        $entryPointUri = $this->backendEntryPointResolver->getUriFromRequest($this->request);
        if (str_starts_with($referrer, (string)$entryPointUri)) {
            // same-origin implies same-site
            return self::TYPE_REFERRER_SAME_ORIGIN | self::TYPE_REFERRER_SAME_SITE;
        }
        if (str_starts_with($referrer, $this->requestHost)) {
            return self::TYPE_REFERRER_SAME_SITE;
        }
        return 0;
    }
}
