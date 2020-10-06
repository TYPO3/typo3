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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Service for determining environment params
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 * @deprecated since v11.2, will be removed in v12.0.
 */
class EnvironmentService implements SingletonInterface
{
    /**
     * @var bool|null
     */
    protected $isFrontendMode;

    public function __construct()
    {
        trigger_error(__CLASS__ . ' will be removed in TYPO3 v12, use the PSR-7 Request and the ApplicationType instead.', E_USER_DEPRECATED);
    }

    /**
     * Detects if frontend application has been called.
     *
     * @return bool
     */
    public function isEnvironmentInFrontendMode(): bool
    {
        $this->initialize();
        if ($this->isFrontendMode !== null) {
            return $this->isFrontendMode;
        }
        // Frontend mode stays false if backend or cli without request object
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend();
    }

    /**
     * Detects if backend application has been called.
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
        // Frontend mode stays false if backend or cli without request object
        $this->isFrontendMode = ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend();
    }

    /**
     * A helper method for tests to simulate application behavior, should only be used within TYPO3 Core
     *
     * @param bool $isFrontendMode
     * @internal only used for testing purposes and can be removed at any time.
     */
    public function setFrontendMode(bool $isFrontendMode): void
    {
        $this->isFrontendMode = $isFrontendMode;
    }
}
