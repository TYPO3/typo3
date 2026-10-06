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

namespace TYPO3\CMS\Install\Tests\Unit\Service\Fixtures;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Install\Service\SessionService;

/**
 * Exposes the session cookie verification and feeds it with php ini values instead of reading
 * the ones of the process running the test suite. The backend user record is provided
 * directly instead of being fetched from the database.
 */
final class AccessibleSessionService extends SessionService
{
    /**
     * @var array<string, bool|null>
     */
    public array $iniValues = [];

    public bool $iniGetIsDisabled = false;

    /**
     * @var array<string, int>|null
     */
    public ?array $backendUserRecord = null;

    public function callLogInsecureSessionCookieSettings(bool $https): void
    {
        $this->logInsecureSessionCookieSettings($https);
    }

    protected function getIniValueBoolean($configOption)
    {
        if ($this->iniGetIsDisabled) {
            throw new \Error('Call to undefined function ini_get()', 1788340725);
        }
        return $this->iniValues[$configOption] ?? null;
    }

    protected function getBackendUserRecord(ContainerInterface $container, int $uid): ?array
    {
        return $this->backendUserRecord;
    }
}
