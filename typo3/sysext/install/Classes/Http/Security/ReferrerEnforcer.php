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

namespace TYPO3\CMS\Install\Http\Security;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Security\ReferrerEnforcer as CoreReferrerEnforcer;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Treats a referrer as same-origin only if it addresses the install tool entry script
 * (`typo3/install.php`) - and not merely the same host, or the same directory, which is
 * shared with the backend entry point.
 *
 * @internal
 */
class ReferrerEnforcer extends CoreReferrerEnforcer
{
    protected function resolveReferrerType(): int
    {
        $referrer = $this->request->getServerParams()['HTTP_REFERER'] ?? '';
        if ($referrer === '') {
            return self::TYPE_REFERRER_EMPTY;
        }
        if (!str_starts_with($referrer, $this->requestHost)) {
            return 0;
        }

        try {
            $referrerUri = new Uri($referrer);
        } catch (\InvalidArgumentException) {
            return 0;
        }

        if ($referrerUri->getPath() === $this->resolveScriptName($this->request)) {
            // same-origin implies same-site
            return self::TYPE_REFERRER_SAME_ORIGIN | self::TYPE_REFERRER_SAME_SITE;
        }
        return self::TYPE_REFERRER_SAME_SITE;
    }

    protected function resolveScriptName(ServerRequestInterface $request): string
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        if ($normalizedParams instanceof NormalizedParams) {
            return $normalizedParams->getScriptName();
        }
        return GeneralUtility::getIndpEnv('SCRIPT_NAME');
    }
}
