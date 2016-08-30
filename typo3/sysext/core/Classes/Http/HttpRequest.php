<?php
namespace TYPO3\CMS\Core\Http;

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
 * HTTP Request Utility class
 *
 * Extends \HTTP_Request2 and sets TYPO3 environment defaults
 */
class HttpRequest extends \HTTP_Request2
{
    /**
     * Default constructor - sets TYPO3 defaults
     *
     * @param string|\Net_Url2 $url Request URL
     * @param string $method Request Method (GET, HEAD or POST). Redirects reset this to GET unless "strict_redirects" is set.
     * @param array $config Configuration for this request instance
     * @link http://pear.php.net/manual/en/package.http.http-request2.config.php
     */
    public function __construct($url = null, $method = self::METHOD_GET, array $config = [])
    {
        parent::__construct($url, $method);
        $this->setConfiguration($config);
    }

    /**
     * Sets the configuration for this object instance.
     * Merges default values with provided $config and overrides all
     * not provided values with those from $TYPO3_CONF_VARS
     *
     * @param array $config Configuration options which override the default configuration
     * @return void
     * @see http://pear.php.net/manual/en/package.http.http-request2.config.php
     */
    public function setConfiguration(array $config = [])
    {
        // set a branded user-agent
        $this->setHeader('user-agent', $GLOBALS['TYPO3_CONF_VARS']['HTTP']['userAgent']);
        $default = [
            'adapter' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['adapter'],
            'connect_timeout' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['connect_timeout'],
            'timeout' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout'],
            'protocol_version' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['protocol_version'],
            'follow_redirects' => (bool)$GLOBALS['TYPO3_CONF_VARS']['HTTP']['follow_redirects'],
            'max_redirects' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['max_redirects'],
            'strict_redirects' => (bool)$GLOBALS['TYPO3_CONF_VARS']['HTTP']['strict_redirects'],
            'proxy_host' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_host'],
            'proxy_port' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_port'],
            'proxy_user' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_user'],
            'proxy_password' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_password'],
            'proxy_auth_scheme' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'],
            'ssl_verify_peer' => (bool)$GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_peer'],
            'ssl_verify_host' => (bool)$GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_host'],
            // we have to deal with Install Tool limitations and set this to NULL if it is empty
            'ssl_cafile' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_cafile'] ?: null,
            'ssl_capath' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_capath'] ?: null,
            'ssl_local_cert' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_local_cert'] ?: null,
            'ssl_passphrase' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_passphrase'] ?: null
        ];
        $configuration = array_merge($default, $config);
        $this->setConfig($configuration);
    }

    /**
     * Downloads chunk by chunk to file instead of saving the whole response into memory.
     * $response->getBody() will be empty.
     * An existing file will be overridden.
     *
     * @param string $directory The absolute path to the directory in which the file is saved.
     * @param string $filename The filename - if not set, it is determined automatically.
     * @return \HTTP_Request2_Response The response with empty body.
     */
    public function download($directory, $filename = '')
    {
        $isAttached = false;
        // Do not store the body in memory
        $this->setConfig('store_body', false);
        // Check if we already attached an instance of download. If so, just reuse it.
        foreach ($this->observers as $observer) {
            if ($observer instanceof Observer\Download) {
                /** @var Observer\Download $attached */
                $observer->setDirectory($directory);
                $observer->setFilename($filename);
                $isAttached = true;
            }
        }
        if (!$isAttached) {
            /** @var Observer\Download $observer */
            $observer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\Observer\Download::class, $directory, $filename);
            $this->attach($observer);
        }
        return $this->send();
    }
}
