<?php
namespace TYPO3\CMS\Core\Utility;

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
 * Class DeprecationUtility
 */
class DeprecationUtility
{

    /**
     * Gets the absolute path to the deprecation log file.
     *
     * @return string Absolute path to the deprecation log file
     */
    public static function getDeprecationLogFileName()
    {
        return PATH_typo3conf . 'deprecation_' . GeneralUtility::shortMD5(
            (PATH_site . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])
        ) . '.log';
    }

    /**
     * Writes a message to the deprecation log.
     *
     * @param string $msg Message (in English).
     * @return void
     */
    public static function logMessage($msg)
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog']) {
            return;
        }
        // Legacy values (no strict comparison, $log can be boolean, string or int)
        $log = $GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'];
        if ($log === true || $log == '1') {
            $log = array('file');
        } else {
            $log = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'], true);
        }
        $date = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' '
            . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] . ': ');
        if (in_array('file', $log) !== false) {
            // Write a longer message to the deprecation log
            $destination = static::getDeprecationLogFileName();
            $file = @fopen($destination, 'a');
            if ($file) {
                @fwrite($file, ($date . $msg . LF));
                @fclose($file);
                GeneralUtility::fixPermissions($destination);
            }
        }
        if (in_array('devlog', $log) !== false) {
            // Copy message also to the developer log
            GeneralUtility::devLog($msg, 'Core', GeneralUtility::SYSLOG_SEVERITY_WARNING);
        }
        // Do not use console in login screen
        if (in_array('console', $log) !== false && isset($GLOBALS['BE_USER']->user['uid'])) {
            DebugUtility::debug($msg, $date, 'Deprecation Log');
        }
    }

    /**
     * Logs a call to a deprecated function.
     * The log message will be taken from the annotation.
     *
     * @return void
     */
    public static function logFunction()
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog']) {
            return;
        }
        $trail = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if ($trail[1]['type']) {
            $function = new \ReflectionMethod($trail[1]['class'], $trail[1]['function']);
        } else {
            $function = new \ReflectionFunction($trail[1]['function']);
        }
        $msg = '';
        if (preg_match('/@deprecated\\s+(.*)/', $function->getDocComment(), $match)) {
            $msg = $match[1];
        }
        // Write a longer message to the deprecation log: <function> <annotion> - <trace> (<source>)
        $logMsg = $trail[1]['class'] . $trail[1]['type'] . $trail[1]['function'];
        $logMsg .= '() - ' . $msg . ' - ' . DebugUtility::debugTrail();
        $logMsg .= ' (' . PathUtility::stripPathSitePrefix($function->getFileName())
            . '#' . $function->getStartLine() . ')';
        self::logMessage($logMsg);
    }
}
