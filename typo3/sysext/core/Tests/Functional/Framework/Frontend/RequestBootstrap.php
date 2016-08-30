<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Frontend;

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
 * Bootstrap for direct CLI Request
 */
class RequestBootstrap
{
    /**
     * @return void
     */
    public static function setGlobalVariables(array $requestArguments = null)
    {
        if (empty($requestArguments)) {
            die('No JSON encoded arguments given');
        }

        if (empty($requestArguments['documentRoot'])) {
            die('No documentRoot given');
        }

        if (empty($requestArguments['requestUrl']) || ($requestUrlParts = parse_url($requestArguments['requestUrl'])) === false) {
            die('No valid request URL given');
        }

        // Populating $_GET and $_REQUEST is query part is set:
        if (isset($requestUrlParts['query'])) {
            parse_str($requestUrlParts['query'], $_GET);
            parse_str($requestUrlParts['query'], $_REQUEST);
        }

        // Populating $_POST
        $_POST = [];
        // Populating $_COOKIE
        $_COOKIE = [];

        // Setting up the server environment
        $_SERVER = [];
        $_SERVER['DOCUMENT_ROOT'] = $requestArguments['documentRoot'];
        $_SERVER['HTTP_USER_AGENT'] = 'TYPO3 Functional Test Request';
        $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = isset($requestUrlParts['host']) ? $requestUrlParts['host'] : 'localhost';
        $_SERVER['SERVER_ADDR'] = $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['_'] = $_SERVER['PATH_TRANSLATED'] = $requestArguments['documentRoot'] . '/index.php';
        $_SERVER['QUERY_STRING'] = (isset($requestUrlParts['query']) ? $requestUrlParts['query'] : '');
        $_SERVER['REQUEST_URI'] = $requestUrlParts['path'] . (isset($requestUrlParts['query']) ? '?' . $requestUrlParts['query'] : '');
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Define HTTPS and server port:
        if (isset($requestUrlParts['scheme'])) {
            if ($requestUrlParts['scheme'] === 'https') {
                $_SERVER['HTTPS'] = 'on';
                $_SERVER['SERVER_PORT'] = '443';
            } else {
                $_SERVER['SERVER_PORT'] = '80';
            }
        }

        // Define a port if used in the URL:
        if (isset($requestUrlParts['port'])) {
            $_SERVER['SERVER_PORT'] = $requestUrlParts['port'];
        }

        if (!is_dir($_SERVER['DOCUMENT_ROOT'])) {
            die('Document root directory "' . $_SERVER['DOCUMENT_ROOT'] . '" does not exist');
        }

        if (!is_file($_SERVER['SCRIPT_FILENAME'])) {
            die('Script file "' . $_SERVER['SCRIPT_FILENAME'] . '" does not exist');
        }
    }

    /**
     * @return void
     */
    public static function executeAndOutput()
    {
        global $TT, $TSFE, $TYPO3_CONF_VARS, $BE_USER, $TYPO3_MISC;

        $result = ['status' => 'failure', 'content' => null, 'error' => null];

        ob_start();
        try {
            chdir($_SERVER['DOCUMENT_ROOT']);
            include($_SERVER['SCRIPT_FILENAME']);
            $result['status'] = 'success';
            $result['content'] = ob_get_contents();
        } catch (\Exception $exception) {
            $result['error'] = $exception->__toString();
        }
        ob_end_clean();

        echo json_encode($result);
    }
}
