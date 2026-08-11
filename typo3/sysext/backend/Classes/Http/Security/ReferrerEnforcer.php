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

/**
 * Treats a referrer as same-origin only if it points into the backend entry point
 * (e.g. `https://example.org/typo3/`) - and not merely to the same host.
 *
 * @internal
 */
readonly class ReferrerEnforcer extends CoreReferrerEnforcer
{
    public function __construct(
        private BackendEntryPointResolver $backendEntryPointResolver,
    ) {}

    protected function resolveReferrerType(ServerRequestInterface $request): int
    {
        $referrer = $request->getServerParams()['HTTP_REFERER'] ?? '';
        if ($referrer === '') {
            return self::TYPE_REFERRER_EMPTY;
        }

        $entryPointUri = $this->backendEntryPointResolver->getUriFromRequest($request);
        if (str_starts_with($referrer, (string)$entryPointUri)) {
            return self::TYPE_REFERRER_SAME_ORIGIN | self::TYPE_REFERRER_SAME_SITE;
        }

        $requestHost = rtrim($this->resolveRequestHost($request), '/') . '/';
        if (str_starts_with($referrer, $requestHost)) {
            return self::TYPE_REFERRER_SAME_SITE;
        }
        return 0;
    }
}
