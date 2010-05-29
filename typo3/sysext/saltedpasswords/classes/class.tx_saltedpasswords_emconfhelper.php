<?php
/***************************************************************
*  Copyright notice
*
*  (c) Steffen Ritter (info@rs-websystems.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
	// Make sure that we are executed only in TYPO3 context
if (!defined ('TYPO3_MODE')) die ('Access denied.');


/**
 * class providing configuration checks for saltedpasswords.
 *
 * @author	Steffen Ritter <info@rs-websystems.de>
 *
 * @since	2009-09-04
 * @package	TYPO3
 * @subpackage	tx_saltedpasswords
 */
class tx_saltedpasswords_emconfhelper {
	/**
	 * @var	integer
	 */
	protected $errorType = t3lib_FlashMessage::OK;

	/**
	 * @var	string
	 */
	protected $header;

	/**
	 * @var	string
	 */
	protected $preText;

	/*
	 * @var	array
	 */
	protected $problems = array();

	/**
	 * Set the error level if no higher level
	 * is set already
	 *
	 * @param	string		$level: one out of error, ok, warning, info
	 * @return	void
	 */
	private function setErrorLevel($level) {

		switch ($level) {
			case 'error':
				$this->errorType = t3lib_FlashMessage::ERROR;
				$this->header = 'Errors found in your configuration';
				$this->preText = 'SaltedPasswords will not work until these problems have been resolved:<br />';
			break;
			case 'warning':
				if ($this->errorType < t3lib_FlashMessage::ERROR) {
					$this->errorType = t3lib_FlashMessage::WARNING;
					$this->header = 'Warnings about your configuration';
					$this->preText = 'SaltedPasswords might behave different than expectated:<br />';
				}
			break;
			case 'info':
				if ($this->errorType < t3lib_FlashMessage::WARNING) {
					$this->errorType = t3lib_FlashMessage::INFO;
					$this->header = 'Additional information';
					$this->preText = '<br />';
				}
			break;
			case 'ok':
					// TODO: Remove INFO condition as it has lower importance
				if ($this->errorType < t3lib_FlashMessage::WARNING && $this->errorType != t3lib_FlashMessage::INFO) {
					$this->errorType = t3lib_FlashMessage::OK;
					$this->header = 'No errors were found';
					$this->preText = 'SaltedPasswords has been configured correctly and works as expected.<br />';
				}
			break;
		}
	}

	/**
	 * Renders the flash messages if problems have been found.
	 *
	 * @return	string		The flash message as HTML.
	 */
	private function renderFlashMessage() {
		$message = '';
			// if there are problems, render them into an unordered list
		if (count($this->problems) > 0) {
			$message = <<< EOT
<ul>
	<li>###PROBLEMS###</li>
</ul>
EOT;
			$message = str_replace('###PROBLEMS###', implode('<br />&nbsp;</li><li>', $this->problems), $message);

			if ($this->errorType > t3lib_FlashMessage::OK) {
				$message .= <<< EOT
<br />
Note, that a wrong configuration might have impact on the security of
your TYPO3 installation and the usability of the backend.
EOT;
			}
		}

		if (empty($message)) {
			$this->setErrorLevel('ok');
		}

		$message = $this->preText . $message;
		$flashMessage = t3lib_div::makeInstance('t3lib_FlashMessage', $message, $this->header, $this->errorType);

		return $flashMessage->render();
	}

	/**
	 * Initializes this object.
	 *
	 * @return void
	 */
	private function init() {
		$requestSetup = $this->processPostData((array)$_REQUEST['data']);
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['saltedpasswords']);
		$this->extConf['BE'] = array_merge((array)$extConf['BE.'], (array)$requestSetup['BE.']);
		$this->extConf['FE'] = array_merge((array)$extConf['FE.'], (array)$requestSetup['FE.']);
		$GLOBALS['LANG']->includeLLFile('EXT:saltedpasswords/locallang.xml');
	}

	/**
	 * Checks the backend configuration and shows a message if necessary.
	 *
	 * @param	array				$params: Field information to be rendered
	 * @param	t3lib_tsStyleConfig	$pObj: The calling parent object.
	 * @return	string				Messages as HTML if something needs to be reported
	 */
	public function checkConfigurationBackend(array $params, t3lib_tsStyleConfig $pObj) {
		$this->init();
		$extConf = $this->extConf['BE'];

			// the backend is called over SSL
		$SSL = (($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] > 0 ? TRUE : FALSE) && ($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'] != 'superchallenged'));
			// rsaAuth is loaded/active
		$RSAauth = (t3lib_extMgm::isLoaded('rsaauth') && ($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'] == 'rsa'));

		if ($extConf['enabled']) {
				// SSL configured?
			if ($SSL) {
				$this->setErrorLevel('ok');
				$problems[] = 'The backend is configured to use SaltedPasswords over SSL.';
			} elseif ($RSAauth) {
				$this->setErrorLevel('ok');
				$problems[] = 'The backend is configured to use SaltedPasswords with RSA authentification.';
			} else {
				$this->setErrorLevel('error');
				$problems[] = <<< EOT
Backend requirements for SaltedPasswords are not met, therefore the
authentication will not work even if it was explicitely enabled for backend
usage:<br />
<ul>
	<li>Install the "rsaauth" extension and use the Install Tool to set the
		Login Security Level for the backend to "rsa"
		(\$TYPO3_CONF_VARS['BE']['loginSecurityLevel'])</li>

	<li>If you have the option to use SSL, you can also configure your
		backend for SSL usage:<br />
		Use the Install Tool to set the Security-Level for the backend
		to "normal" (\$TYPO3_CONF_VARS['BE']['loginSecurityLevel']) and
		the SSL-locking option to a value greater than "0"
		(see description - \$TYPO3_CONF_VARS['BE']['lockSSL'])</li>
</ul>
<br />
It is also possible to use "lockSSL" and "rsa" Login Security Level at the same
time.
EOT;
			}

				// only saltedpasswords as authsservice
			if ($extConf['onlyAuthService']) {
					// warn user taht the combination with "forceSalted" may lock him out from Backend
				if ($extConf['forceSalted']) {
					$this->setErrorLevel('warning');
					$problems[] = <<< EOT
SaltedPasswords has been configured to be the only authentication service for
the backend. Additionally, usage of salted passwords is enforced (forceSalted).
The result is that there is no chance to login with users not having a salted
password hash.<br />
<strong><i>WARNING:</i></strong> This may lock you out of the backend!
EOT;
				} else {
						// inform the user that things like openid won't work anymore
					$this->setErrorLevel('info');
					$problems[] = <<< EOT
SaltedPasswords has been configured to be the only authentication service for
the backend. This means that other services like "ipauth", "openid", etc. will
be ignored (except "rsauth", which is implicitely used).
EOT;
				}
			}
				// forceSalted is set
			if ($extConf['forceSalted'] && !$extConf['onlyAuthService']) {
				$this->setErrorLevel('warning');
				$problems[] = <<< EOT
SaltedPasswords has been configured to enforce salted passwords (forceSalted).
<br />
This means that only passwords in the format of this extension will succeed for
login.<br />
<strong><i>IMPORTANT:</i></strong> This has the effect that passwords that are set from
the Install Tool will not work!
EOT;
			}
				// updatePasswd wont work with "forceSalted"
			if ($extConf['updatePasswd'] && $extConf['forceSalted']) {
				$this->setErrorLevel('error');
				$problems[] = <<< EOT
SaltedPasswords is configured wrong and will not work as expected:<br />
It is not possible to set "updatePasswd" and "forceSalted" at the same time.
Please disable either one of them.
EOT;
			}
				// check if the configured hash-method is available on system
			if (!$instance = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL,'BE') || !$instance->isAvailable()) {
				$this->setErrorLevel('error');
				$problems[] = <<< EOT
The selected method for hashing your salted passwords is not available on this
system! Please check your configuration.
EOT;
			}

		} else {
			// not enabled warning
			$this->setErrorLevel('info');
			$problems[] = 'SaltedPasswords has been disabled for backend users.';
		}

		$this->problems = $problems;

		return $this->renderFlashMessage();
	}

	/**
	 * Checks the frontend configuration and shows a message if necessary.
	 *
	 * @param	array				$params: Field information to be rendered
	 * @param	t3lib_tsStyleConfig	$pObj: The calling parent object.
	 * @return	string				Messages as HTML if something needs to be reported
	 */
	public function checkConfigurationFrontend(array $params, t3lib_tsStyleConfig $pObj) {
		$this->init();
		$extConf = $this->extConf['FE'];

		if ($extConf['enabled']) {
				// inform the user if securityLevel in FE is superchallenged or blank --> extension won't work
			if (!t3lib_div::inList('normal,rsa', $GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'])) {
				$this->setErrorLevel('info');
				$problems[] = <<< EOT
<strong>IMPORTANT:</strong><br />
Frontend requirements for SaltedPasswords are not met, therefore the
authentication will not work even if it was explicitely enabled for frontend
usage:<br />
<ul>
	<li>Install the "rsaauth" extension and use the Install Tool to set the
		Login Security Level for the frontend to "rsa"
		(\$TYPO3_CONF_VARS['FE']['loginSecurityLevel'])</li>

	<li>Alternatively, use the Install Tool to set the Login Security Level
		for the frontend to "normal"
		(\$TYPO3_CONF_VARS['FE']['loginSecurityLevel'])</li>
</ul>
<br />
Make sure that the Login Security Level is not set to "" or "superchallenged"!
EOT;
			}
				// only saltedpasswords as authsservice
			if ($extConf['onlyAuthService']) {
					// warn user taht the combination with "forceSalted" may lock him out from frontend
				if ($extConf['forceSalted']) {
					$this->setErrorLevel('warning');
					$problems[] = <<< EOT
SaltedPasswords has been configured to enforce salted passwords (forceSalted).
<br />
This means that only passwords in the format of this extension will succeed for
login.<br />
<strong><i>IMPORTANT:</i></strong> Because of this, it is not possible to login with
users not having a salted password hash (e.g. existing frontend users).
EOT;
				} else {
						// inform the user that things like openid won't work anymore
					$this->setErrorLevel('info');
					$problems[] = <<< EOT
SaltedPasswords has been configured to be the only authentication service for
frontend logins. This means that other services like "ipauth", "openid", etc.
will be ignored.
EOT;
				}
			}
				// forceSalted is set
			if ($extConf['forceSalted'] && !$extConf['onlyAuthService']) {
				$this->setErrorLevel('warning');
				$problems[] = <<< EOT
SaltedPasswords has been configured to enforce salted passwords (forceSalted).
<br />
This means that only passwords in the format of this extension will succeed for
login.<br />
<strong><i>IMPORTANT:</i></strong> This has the effect that passwords that were set
before SaltedPasswords was used will not work (in fact, they need to be
redefined).
EOT;
			}
				// updatePasswd wont work with "forceSalted"
			if ($extConf['updatePasswd'] && $extConf['forceSalted']) {
				$this->setErrorLevel('error');
				$problems[] = <<< EOT
SaltedPasswords is configured wrong and will not work as expected:<br />
It is not possible to set "updatePasswd" and "forceSalted" at the same time.
Please disable either one of them.
EOT;
			}

		} else {
			// not enabled warning
			$this->setErrorLevel('info');
			$problems[] = 'SaltedPasswords has been disabled for frontend users.';
		}

		$this->problems = $problems;

		return $this->renderFlashMessage();
	}

	/**
	 * Renders a selector element that allows to select the hash method to be used.
	 *
	 * @param	array				$params: Field information to be rendered
	 * @param	t3lib_tsStyleConfig	$pObj: The calling parent object.
	 * @param	string				$disposal: The configuration disposal ('FE' or 'BE')
	 * @return	string				The HTML selector
	 */
	protected function buildHashMethodSelector(array $params, t3lib_tsStyleConfig $pObj, $disposal) {
		$this->init();
		$fieldName = substr($params['fieldName'], 5, -1);
		$unknownVariablePleaseRenameMe = '\'' . substr(md5($fieldName), 0, 10) . '\'';

		$p_field = '';

		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods'] as $class => $reference) {
			$classInstance = t3lib_div::getUserObj($reference, 'tx_');

			if ($classInstance instanceof tx_saltedpasswords_salts && $classInstance->isAvailable()) {
				$sel = ($this->extConf[$disposal]['saltedPWHashingMethod'] == $class) ? ' selected="selected" ' : '';
				$label = 'ext.saltedpasswords.title.' . $class;
				$p_field .= '<option value="' . htmlspecialchars($class) . '"' . $sel . '>' . $GLOBALS['LANG']->getLL($label) . '</option>';
			}
		}

		$p_field = '<select id="' . $fieldName . '" name="' . $params['fieldName'] . '" onChange="uFormUrl(' . $unknownVariablePleaseRenameMe . ')">' . $p_field . '</select>';

		return $p_field;
	}

	/**
	 * Renders a selector element that allows to select the hash method to be used (frontend disposal).
	 *
	 * @param	array				$params: Field information to be rendered
	 * @param	t3lib_tsStyleConfig	$pObj: The calling parent object.
	 * @return	string				The HTML selector
	 */
	public function buildHashMethodSelectorFE(array $params, t3lib_tsStyleConfig $pObj) {
		return $this->buildHashMethodSelector($params, $pObj, 'FE');
	}

	/**
	 * Renders a selector element that allows to select the hash method to be used (backend disposal)
	 *
	 * @param	array				$params: Field information to be rendered
	 * @param	t3lib_tsStyleConfig	$pObj: The calling parent object.
	 * @return	string				The HTML selector
	 */
	public function buildHashMethodSelectorBE(array $params, t3lib_tsStyleConfig $pObj) {
		return $this->buildHashMethodSelector($params, $pObj, 'BE');
	}

	/**
	 * Processes the information submitted by the user using a POST request and
	 * transforms it to a TypoScript node notation.
	 *
	 * @param	array		$postArray: Incoming POST information
	 * @return	array		Processed and transformed POST information
	 */
	private function processPostData(array $postArray = array()) {
		foreach ($postArray as $key => $value) {
			// TODO: Explain
			$parts = explode('.', $key, 2);

			if (count($parts)==2) {
				// TODO: Explain
				$value = $this->processPostData(array($parts[1] => $value));
				$postArray[$parts[0].'.'] = array_merge((array)$postArray[$parts[0].'.'], $value);
			} else {
				// TODO: Explain
				$postArray[$parts[0]] = $value;
			}
		}

		return $postArray;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/saltedpasswords/classes/class.tx_saltedpasswords_emconfhelper.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/saltedpasswords/classes/class.tx_saltedpasswords_emconfhelper.php']);
}
?>