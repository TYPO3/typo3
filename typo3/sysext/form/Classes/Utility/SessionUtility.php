<?php
namespace TYPO3\CMS\Form\Utility;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * A session utility
 */
class SessionUtility implements SingletonInterface
{
    /**
     * Session data
     *
     * @var array
     */
    protected $sessionData = [];

    /**
     * Prefix for the session
     *
     * @var string
     */
    protected $formPrefix = '';

    /**
     * @var TypoScriptFrontendController
     */
    protected $frontendController;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->frontendController = $GLOBALS['TSFE'];
    }

    /**
     * Store the form input in a session
     *
     * @param string $formPrefix
     * @return void
     */
    public function initSession($formPrefix = '')
    {
        $this->setFormPrefix($formPrefix);
        if ($this->frontendController->loginUser) {
            $this->sessionData = $this->frontendController->fe_user->getKey('user', $this->formPrefix);
        } else {
            $this->sessionData = $this->frontendController->fe_user->getKey('ses', $this->formPrefix);
        }
    }

    /**
     * Store the form input in a session
     *
     * @return void
     */
    public function storeSession()
    {
        if ($this->frontendController->loginUser) {
            $this->frontendController->fe_user->setKey('user', $this->formPrefix, $this->getSessionData());
        } else {
            $this->frontendController->fe_user->setKey('ses', $this->formPrefix, $this->getSessionData());
        }
        $this->frontendController->storeSessionData();
    }

    /**
     * Destroy the session data for the form
     *
     * @return void
     */
    public function destroySession()
    {
        $this->removeFiles();
        if ($this->frontendController->loginUser) {
            $this->frontendController->fe_user->setKey('user', $this->formPrefix, null);
        } else {
            $this->frontendController->fe_user->setKey('ses', $this->formPrefix, null);
        }
        $this->frontendController->storeSessionData();
    }

    /**
     * Set the session Data by $key
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setSessionData($key, $value)
    {
        $this->sessionData[$key] = $value;
    }

    /**
     * Retrieve a member of the $sessionData variable
     *
     * If no $key is passed, returns the entire $sessionData array
     *
     * @param string $key Parameter to search for
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns NULL if key does not exist
     */
    public function getSessionData($key = null, $default = null)
    {
        if ($key === null) {
            return $this->sessionData;
        }
        return isset($this->sessionData[$key]) ? $this->sessionData[$key] : $default;
    }

    /**
     * Set the form prefix
     *
     * @param string $formPrefix
     * @return array
     */
    public function setFormPrefix($formPrefix)
    {
        $this->formPrefix = $formPrefix;
    }

    /**
     * Remove uploaded files from the typo3temp
     *
     * @return void
     */
    protected function removeFiles()
    {
        $sessionData = $this->getSessionData();
        if (is_array($sessionData)) {
            foreach ($sessionData as $fieldName => $values) {
                if (is_array($values)) {
                    foreach ($values as $file) {
                        if (isset($file['tempFilename'])) {
                            GeneralUtility::unlink_tempfile($file['tempFilename']);
                        }
                    }
                }
            }
        }
    }
}
