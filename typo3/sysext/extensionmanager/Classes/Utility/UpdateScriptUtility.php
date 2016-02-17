<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

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
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * Utility to find and execute class.ext_update.php scripts of extensions
 */
class UpdateScriptUtility
{
    /**
     * Returns true, if ext_update class says it wants to run.
     *
     * @param string $extensionKey extension key
     * @return mixed NULL, if update is not available, else update script return
     */
    public function executeUpdateIfNeeded($extensionKey)
    {
        $className = $this->requireUpdateScript($extensionKey);
        $scriptObject = GeneralUtility::makeInstance($className);

        // old em always assumed the method exist, we do so too.
        // @TODO: Make this smart, let scripts implement interfaces
        // @TODO: With current ext_update construct it is impossible to enforce some type of return
        return $scriptObject->access() ? $scriptObject->main() : null;
    }

    /**
     * Require update script.
     * Throws exception if update script does not exist, so checkUpdateScriptExists()
     * should be called before
     *
     * @param string $extensionKey
     * @return string Class name of update script
     * @throws ExtensionManagerException
     */
    protected function requireUpdateScript($extensionKey)
    {
        if (class_exists('ext_update', false)) {
            throw new ExtensionManagerException(
                'class ext_update for this run does already exist, requiring impossible',
                1359748085
            );
        }

        $className = $this->determineUpdateClassName($extensionKey);
        if ($className === '') {
            throw new ExtensionManagerException(
                'Requested update script of extension does not exist',
                1359747976
            );
        }
        return $className;
    }

    /**
     * Checks if an update class file exists.
     *
     * Does not check if some update is needed.
     *
     * @param string $extensionKey Extension key
     * @return bool TRUE, if there is some update script and it needs to be executed
     */
    public function checkUpdateScriptExists($extensionKey)
    {
        $className = $this->determineUpdateClassName($extensionKey);
        if ($className !== '') {
            $updater = GeneralUtility::makeInstance($className);
            return $updater->access();
        }
        return false;
    }

    /**
     * Determine the real class name to use
     *
     * @param string $extensionKey
     * @return string Returns the final class name if an update script is present, otherwise empty string
     * @throws ExtensionManagerException If an update script is present but no ext_update class can be loaded
     */
    protected function determineUpdateClassName($extensionKey)
    {
        $updateScript = GeneralUtility::getFileAbsFileName('EXT:' . $extensionKey . '/class.ext_update.php');
        if (!file_exists($updateScript)) {
            return '';
        }

        // get script contents
        $scriptSourceCode = GeneralUtility::getUrl($updateScript);
        // check if it has a namespace
        if (!preg_match('/<\?php.*namespace\s+([^;]+);.*class/is', $scriptSourceCode, $matches)) {
            // if no, rename the class with a unique name
            $className = 'ext_update' . md5($extensionKey . $scriptSourceCode);
            $temporaryFileName = PATH_site . 'typo3temp/var/transient/' . $className . '.php';
            if (!file_exists(GeneralUtility::getFileAbsFileName($temporaryFileName))) {
                $scriptSourceCode = preg_replace('/^\s*class\s+ext_update\s+/m', 'class ' . $className . ' ', $scriptSourceCode);
                GeneralUtility::writeFileToTypo3tempDir($temporaryFileName, $scriptSourceCode);
            }
            $updateScript = $temporaryFileName;
        } else {
            $className = $matches[1] . '\ext_update';
        }
        include_once $updateScript;
        if (!class_exists($className, false)) {
            throw new ExtensionManagerException(
                sprintf('class.ext_update.php of extension "%s" did not declare ext_update class', $extensionKey),
                1428176468
            );
        }

        return $className;
    }
}
