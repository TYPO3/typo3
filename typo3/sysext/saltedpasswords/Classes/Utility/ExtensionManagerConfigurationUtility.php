<?php
namespace TYPO3\CMS\Saltedpasswords\Utility;

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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * class providing configuration checks for saltedpasswords.
 */
class ExtensionManagerConfigurationUtility
{
    /**
     * @var int
     */
    protected $errorType = FlashMessage::OK;

    /**
     * @var string
     */
    protected $header;

    /**
     * @var string
     */
    protected $preText;

    /**
     * @var array
     */
    protected $problems = [];

    /**
     * @var array
     */
    protected $extConf = [];

    /**
     * Set the error level if no higher level
     * is set already
     *
     * @param string $level One out of error, ok, warning, info
     */
    protected function setErrorLevel($level)
    {
        $lang = $this->getLanguageService();
        switch ($level) {
            case 'error':
                $this->errorType = FlashMessage::ERROR;
                $this->header = $lang->getLL('ext.saltedpasswords.configuration.header.errorsFound');
                $this->preText = $lang->getLL('ext.saltedpasswords.configuration.message.errorsFound') . '<br />';
                break;
            case 'warning':
                if ($this->errorType < FlashMessage::ERROR) {
                    $this->errorType = FlashMessage::WARNING;
                    $this->header = $lang->getLL('ext.saltedpasswords.configuration.header.warningsFound');
                    $this->preText = $lang->getLL('ext.saltedpasswords.configuration.message.warningsFound') . '<br />';
                }
                break;
            case 'info':
                if ($this->errorType < FlashMessage::WARNING) {
                    $this->errorType = FlashMessage::INFO;
                    $this->header = $lang->getLL('ext.saltedpasswords.configuration.header.additionalInformation');
                    $this->preText = '<br />';
                }
                break;
            case 'ok':
                // @todo Remove INFO condition as it has lower importance
                if ($this->errorType < FlashMessage::WARNING && $this->errorType != FlashMessage::INFO) {
                    $this->errorType = FlashMessage::OK;
                    $this->header = $lang->getLL('ext.saltedpasswords.configuration.header.noErrorsFound');
                    $this->preText = $lang->getLL('ext.saltedpasswords.configuration.message.noErrorsFound') . '<br />';
                }
                break;
            default:
        }
    }

    /**
     * Renders the messages if problems have been found.
     *
     * @return array an array with errorType and html code
     */
    protected function renderMessage()
    {
        $message = '';
        // If there are problems, render them into an unordered list
        if (!empty($this->problems)) {
            $message = '<ul><li>###PROBLEMS###</li></ul>';
            $message = str_replace('###PROBLEMS###', implode('<br />&nbsp;</li><li>', $this->problems), $message);
            if ($this->errorType > FlashMessage::OK) {
                $message .= '<br />' .
                $this->getLanguageService()->getLL('ext.saltedpasswords.configuration.message.securityWarning');
            }
        }
        if (empty($message)) {
            $this->setErrorLevel('ok');
        }
        $message = $this->preText . $message;

        $class = 'default';
        switch ($this->errorType) {
            case FlashMessage::NOTICE:
                $class = 'notice';
                break;
            case FlashMessage::INFO:
                $class = 'info';
                break;
            case FlashMessage::OK:
                $class = 'success';
                break;
            case FlashMessage::WARNING:
                $class = 'warning';
                break;
            case FlashMessage::ERROR:
                $class = 'danger';
                break;
            default:
        }
        $html = '<div class="panel panel-' . $class . '">' .
                    '<div class="panel-heading">' . $this->header . '</div>' .
                    '<div class="panel-body">' . $message . '</div>' .
                '</div>';
        return [
            'errorType' => $this->errorType,
            'html' => $html
        ];
    }

    /**
     * Initializes this object.
     */
    private function init()
    {
        $requestSetup = $this->processPostData((array)$_REQUEST['data']);
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['saltedpasswords'], ['allowed_classes' => false]);
        $this->extConf['BE'] = array_merge((array)$extConf['BE.'], (array)$requestSetup['BE.']);
        $this->extConf['FE'] = array_merge((array)$extConf['FE.'], (array)$requestSetup['FE.']);
        $this->getLanguageService()->includeLLFile('EXT:saltedpasswords/Resources/Private/Language/locallang.xlf');
    }

    /**
     * Checks the backend configuration and shows a message if necessary.
     * The method returns an array or the HTML code depends on
     * $params['propertyName'] is set or not.
     *
     * @param array $params Field information to be rendered
     * @param \TYPO3\CMS\Core\TypoScript\ConfigurationForm $pObj The calling parent object.
     * @return array|string array with errorType and HTML or only the HTML as string
     */
    public function checkConfigurationBackend(array $params, $pObj)
    {
        $this->init();
        $extConf = $this->extConf['BE'];
        // The backend is called over SSL
        $isBackendCalledOverSsl = (bool)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'];
        $rsaAuthLoaded = ExtensionManagementUtility::isLoaded('rsaauth');
        // SSL configured?
        $lang = $this->getLanguageService();
        if ($isBackendCalledOverSsl) {
            $this->setErrorLevel('ok');
            $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.backendSsl');
        } elseif ($rsaAuthLoaded) {
            $loginSecurityLevel = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']) ?: 'normal';
            if ($loginSecurityLevel === 'rsa') {
                if ($this->isRsaAuthBackendAvailable()) {
                    $this->setErrorLevel('ok');
                    $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.backendRsa');
                } else {
                    // This means that login would fail because rsaauth is not working properly
                    $this->setErrorLevel('error');
                    $problems[] = '<strong>' .
                        $lang->getLL('ext.saltedpasswords.configuration.message.openSslMissing') .
                        '<a href="http://php.net/manual/en/openssl.installation.php" target="_blank">PHP.net</a></strong>.';
                }
            } else {
                // This means that rsaauth is enabled but not used
                $this->setErrorLevel('warning');
                $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.backendSecurityLevelNotRsa');
            }
        } else {
            // This means that we don't use any encryption method
            $this->setErrorLevel('warning');
            $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.rsaInstructionsIntro') . '<br />
				<ul>
				<li>' . $lang->getLL('ext.saltedpasswords.configuration.message.rsaInstructionsFirstItem') . '</li>

				<li>' . nl2br($lang->getLL('ext.saltedpasswords.configuration.message.rsaInstructionsSecondItem')) .
                '</li>
				</ul>
				<br />
				' . $lang->getLL('ext.saltedpasswords.configuration.message.rsaInstructionsFootnote');
        }
        // Only saltedpasswords as authsservice
        if ($extConf['onlyAuthService']) {
            // Warn user that the combination with "forceSalted" may lock him
            // out from Backend
            if ($extConf['forceSalted']) {
                $this->setErrorLevel('warning');
                $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.warningForceSalted') . '<br />
					<strong><i>' . $lang->getLL('ext.saltedpasswords.configuration.label.warning') . '</i></strong> ' .
                    $lang->getLL('ext.saltedpasswords.configuration.message.warningForceSaltedNoteForBackend');
            } else {
                // Inform the user that things like openid won't work anymore
                $this->setErrorLevel('info');
                $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.infoOnlyBackendAuthService');
            }
        }
        // forceSalted is set
        if ($extConf['forceSalted'] && !$extConf['onlyAuthService']) {
            $this->setErrorLevel('info');
            $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.infoForceSalted') .
                ' <br /> ' . $lang->getLL('ext.saltedpasswords.configuration.message.infoForceSaltedNote');
        }
        // updatePasswd wont work with "forceSalted"
        if ($extConf['updatePasswd'] && $extConf['forceSalted']) {
            $this->setErrorLevel('error');
            $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.errorForceSaltedAndUpdatePassword') .
                '<br /> ' .
                $lang->getLL('ext.saltedpasswords.configuration.message.errorForceSaltedAndUpdatePasswordReason');
        }
        // Check if the configured hash-method is available on system
        $instance = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(null, 'BE');
        if ($instance === null || !$instance->isAvailable()) {
            $this->setErrorLevel('error');
            $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.errorHashMethodNotAvailable');
        }
        $this->problems = $problems;
        $result = $this->renderMessage();
        if (!empty($params['propertyName'])) {
            return $result['html'];
        }
        return $result;
    }

    /**
     * Checks if rsaauth is able to obtain a backend
     *
     * @return bool
     */
    protected function isRsaAuthBackendAvailable()
    {
        // Try to instantiate an RSAauth backend. If this does not work,
        // it means that OpenSSL is not usable
        /** @var \TYPO3\CMS\Rsaauth\Backend\BackendFactory $rsaauthBackendFactory */
        $rsaauthBackendFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Rsaauth\Backend\BackendFactory::class);
        $backend = $rsaauthBackendFactory->getBackend();
        return $backend !== null;
    }

    /**
     * Checks the frontend configuration and shows a message if necessary.
     * The method returns an array or the HTML code depends on
     * $params['propertyName'] is set or not.
     *
     * @param array $params Field information to be rendered
     * @param \TYPO3\CMS\Core\TypoScript\ConfigurationForm $pObj The calling parent object.
     * @return array|string array with errorType and HTML or only the HTML as string
     */
    public function checkConfigurationFrontend(array $params, $pObj)
    {
        $this->init();
        $extConf = $this->extConf['FE'];
        $problems = [];
        $lang = $this->getLanguageService();
        if ($extConf['enabled']) {
            $loginSecurityLevel = trim($GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel']) ?: 'normal';
            if ($loginSecurityLevel !== 'normal' && $loginSecurityLevel !== 'rsa') {
                $this->setErrorLevel('info');
                $problems[] = '<strong>' . $lang->getLL('ext.saltedpasswords.configuration.label.important') .
                    '</strong><br /> ' .
                    $lang->getLL('ext.saltedpasswords.configuration.message.infoLoginSecurityLevelDifferent') .
                    '<br />
					<ul>
						<li>' .
                    $lang->getLL('ext.saltedpasswords.configuration.message.infoLoginSecurityLevelDifferentFirstItem') .
                    '</li>

						<li>' .
                    $lang->getLL('ext.saltedpasswords.configuration.message.infoLoginSecurityLevelDifferentSecondItem') .
                    '</li>
					</ul>
					<br />
					' . $lang->getLL('ext.saltedpasswords.configuration.message.infoLoginSecurityLevelDifferentNote');
            } elseif ($loginSecurityLevel === 'rsa') {
                if (ExtensionManagementUtility::isLoaded('rsaauth')) {
                    if ($this->isRsaAuthBackendAvailable()) {
                        $this->setErrorLevel('ok');
                        $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.okFeRsaauthLoaded');
                    } else {
                        // This means that login would fail because rsaauth is not working properly
                        $this->setErrorLevel('error');
                        $problems[] = '<strong>' . $lang->getLL('ext.saltedpasswords.configuration.message.openSslMissing') .
                            ' <a href="http://php.net/manual/en/openssl.installation.php" target="_blank">PHP.net</a></strong>.';
                    }
                } else {
                    // Rsaauth is not installed but configured to be used
                    $this->setErrorLevel('warning');
                    $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.warningRsaauthNotInstalledButConfigured');
                }
            }
            // Only saltedpasswords as authsservice
            if ($extConf['onlyAuthService']) {
                // Warn user that the combination with "forceSalted" may lock
                // him out from frontend
                if ($extConf['forceSalted']) {
                    $this->setErrorLevel('warning');
                    $problems[] = nl2br($lang->getLL('ext.saltedpasswords.configuration.message.infoForceSalted')) .
                        '<strong><i>' . $lang->getLL('ext.saltedpasswords.configuration.label.important') .
                        '</i></strong> ' . $lang->getLL('ext.saltedpasswords.configuration.message.warningForceSaltedNoteForFrontend');
                } else {
                    // Inform the user that things like openid won't work anymore
                    $this->setErrorLevel('info');
                    $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.infoOnlyFrontendAuthService');
                }
            }
            // forceSalted is set
            if ($extConf['forceSalted'] && !$extConf['onlyAuthService']) {
                $this->setErrorLevel('warning');
                $problems[] = nl2br($lang->getLL('ext.saltedpasswords.configuration.message.infoForceSalted')) .
                    '<strong><i>' . $lang->getLL('ext.saltedpasswords.configuration.label.important') .
                    '</i></strong> ' . $lang->getLL('ext.saltedpasswords.configuration.message.warningForceSaltedNote2');
            }
            // updatePasswd wont work with "forceSalted"
            if ($extConf['updatePasswd'] && $extConf['forceSalted']) {
                $this->setErrorLevel('error');
                $problems[] = nl2br($lang->getLL('ext.saltedpasswords.configuration.message.errorForceSaltedAndUpdatePassword'));
            }
        } else {
            // Not enabled warning
            $this->setErrorLevel('info');
            $problems[] = $lang->getLL('ext.saltedpasswords.configuration.message.infoSaltedpasswordsFrontendDisabled');
        }
        $this->problems = $problems;
        $result = $this->renderMessage();
        if (!empty($params['propertyName'])) {
            return $result['html'];
        }
        return $result;
    }

    /**
     * Renders a selector element that allows to select the hash method to be used.
     *
     * @param array $params Field information to be rendered
     * @param \TYPO3\CMS\Core\TypoScript\ConfigurationForm $pObj The calling parent object.
     * @param string $disposal The configuration disposal ('FE' or 'BE')
     * @return string The HTML selector
     */
    protected function buildHashMethodSelector(array $params, $pObj, $disposal)
    {
        $this->init();
        $propertyName = $params['propertyName'];
        $unknownVariablePleaseRenameMe = '\'' . substr(md5($propertyName), 0, 10) . '\'';
        $pField = '';
        $registeredMethods = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getRegisteredSaltedHashingMethods();
        foreach ($registeredMethods as $class => $reference) {
            $classInstance = GeneralUtility::getUserObj($reference);
            if ($classInstance instanceof \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface && $classInstance->isAvailable()) {
                $sel = $this->extConf[$disposal]['saltedPWHashingMethod'] == $class ? ' selected="selected" ' : '';
                $label = 'ext.saltedpasswords.title.' . strtolower(end(explode('\\', $class)));
                $pField .= '<option value="' . htmlspecialchars($class) . '"' . $sel . '>' . $GLOBALS['LANG']->getLL($label) . '</option>';
            }
        }
        $pField = '<select class="form-control" id="' . $propertyName . '" name="' . $params['fieldName'] .
            '" onChange="uFormUrl(' . $unknownVariablePleaseRenameMe . ')">' . $pField . '</select>';
        return $pField;
    }

    /**
     * Renders a selector element that allows to select the hash method to be
     * used (frontend disposal).
     *
     * @param array $params Field information to be rendered
     * @param \TYPO3\CMS\Core\TypoScript\ConfigurationForm $pObj The calling parent object.
     * @return string The HTML selector
     */
    public function buildHashMethodSelectorFE(array $params, $pObj)
    {
        return $this->buildHashMethodSelector($params, $pObj, 'FE');
    }

    /**
     * Renders a selector element that allows to select the hash method to
     * be used (backend disposal)
     *
     * @param array $params Field information to be rendered
     * @param \TYPO3\CMS\Core\TypoScript\ConfigurationForm $pObj The calling parent object.
     * @return string The HTML selector
     */
    public function buildHashMethodSelectorBE(array $params, $pObj)
    {
        return $this->buildHashMethodSelector($params, $pObj, 'BE');
    }

    /**
     * Processes the information submitted by the user using a POST request and
     * transforms it to a TypoScript node notation.
     *
     * @param array $postArray Incoming POST information
     * @return array Processed and transformed POST information
     */
    protected function processPostData(array $postArray = [])
    {
        foreach ($postArray as $key => $value) {
            // @todo Explain
            $parts = explode('.', $key, 2);
            if (count($parts) == 2) {
                // @todo Explain
                $value = $this->processPostData([$parts[1] => $value]);
                $postArray[$parts[0] . '.'] = array_merge((array)$postArray[$parts[0] . '.'], $value);
            } else {
                // @todo Explain
                $postArray[$parts[0]] = $value;
            }
        }
        return $postArray;
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
