<?php
declare(strict_types = 1);

namespace TYPO3\CMS\FrontendLogin\Configuration;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class that holds and manages all states relevant for handling redirects
 *
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
final class RedirectConfiguration
{
    /**
     * @var array
     */
    private $modes;

    /**
     * @var string
     */
    private $firstMode;

    /**
     * @var int
     */
    private $pageOnLogin;

    /**
     * @var string
     */
    private $domains;

    /**
     * @var int
     */
    private $pageOnLoginError;

    /**
     * @var int
     */
    private $pageOnLogout;

    public function __construct(string $mode, string $firstMode, int $pageOnLogin, string $domains, int $pageOnLoginError, int $pageOnLogout)
    {
        $this->modes = GeneralUtility::trimExplode(',', $mode ?? '', true);
        $this->firstMode = $firstMode;
        $this->pageOnLogin = $pageOnLogin;
        $this->domains = $domains;
        $this->pageOnLoginError = $pageOnLoginError;
        $this->pageOnLogout = $pageOnLogout;
    }

    /**
     * @return array
     */
    public function getModes(): array
    {
        return $this->modes;
    }

    /**
     * @return string
     */
    public function getFirstMode(): string
    {
        return $this->firstMode;
    }

    /**
     * @return int
     */
    public function getPageOnLogin(): int
    {
        return $this->pageOnLogin;
    }

    /**
     * @return string
     */
    public function getDomains(): string
    {
        return $this->domains;
    }

    /**
     * @return int
     */
    public function getPageOnLoginError(): int
    {
        return $this->pageOnLoginError;
    }

    /**
     * @return int
     */
    public function getPageOnLogout(): int
    {
        return $this->pageOnLogout;
    }
}
