<?php
namespace TYPO3\CMS\Extbase\Service;

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

use TYPO3\CMS\Core\Core\Environment;

/**
 * Service for determining environment params
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class EnvironmentService implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Detects if TYPO3_MODE is defined and its value is "FE"
     *
     * @return bool
     */
    public function isEnvironmentInFrontendMode()
    {
        return (defined('TYPO3_MODE') && TYPO3_MODE === 'FE') ?: false;
    }

    /**
     * Detects if TYPO3_MODE is defined and its value is "BE"
     *
     * @return bool
     */
    public function isEnvironmentInBackendMode()
    {
        return (defined('TYPO3_MODE') && TYPO3_MODE === 'BE') ?: false;
    }

    /**
     * Detects if we are running a script from the command line.
     *
     * @return bool
     * @deprecated since TYPO3 v9.4 and will be removed in TYPO3 v10.0
     * @see Environment::isCli()
     */
    public function isEnvironmentInCliMode()
    {
        trigger_error('EnvironmentService::isEnvironmentInCliMode will be removed in TYPO3 v10.0. Use Environment::isCli() instead.', E_USER_DEPRECATED);
        return Environment::isCli();
    }

    /**
     * @return string
     */
    public function getServerRequestMethod()
    {
        return isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }
}
