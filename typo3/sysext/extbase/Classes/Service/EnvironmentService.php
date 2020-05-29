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

namespace TYPO3\CMS\Extbase\Service;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Service for determining environment params
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class EnvironmentService implements SingletonInterface
{
    /**
     * @var bool|null
     */
    protected $isFrontendMode;

    /**
     * Detects if TYPO3_MODE is defined and its value is "FE"
     *
     * @return bool
     */
    public function isEnvironmentInFrontendMode(): bool
    {
        $this->initialize();
        if ($this->isFrontendMode !== null) {
            return $this->isFrontendMode;
        }
        return (defined('TYPO3_MODE') && TYPO3_MODE === 'FE') ?: false;
    }

    /**
     * Detects if TYPO3_MODE is defined and its value is "BE"
     *
     * @return bool
     */
    public function isEnvironmentInBackendMode(): bool
    {
        return !$this->isEnvironmentInFrontendMode();
    }

    protected function initialize(): void
    {
        if ($this->isFrontendMode !== null) {
            return;
        }
        if (defined('TYPO3_MODE')) {
            $this->isFrontendMode = TYPO3_MODE === 'FE';
        }
    }

    /**
     * A helper method for tests to simulate TYPO3_MODE behavior, should only be used within TYPO3 Core
     *
     * @param bool $isFrontendMode
     * @internal only used for testing purposes and can be removed at any time.
     */
    public function setFrontendMode(bool $isFrontendMode): void
    {
        $this->isFrontendMode = $isFrontendMode;
    }
}
