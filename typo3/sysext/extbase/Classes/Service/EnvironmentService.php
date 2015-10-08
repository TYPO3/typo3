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

/**
 * Service for determining environment params
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
     */
    public function isEnvironmentInCliMode()
    {
        return $this->isEnvironmentInBackendMode() && defined('TYPO3_cliMode') && TYPO3_cliMode === true;
    }

    /**
     * @return string
     */
    public function getServerRequestMethod()
    {
        return isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }
}
