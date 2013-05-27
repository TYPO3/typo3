<?php
namespace TYPO3\CMS\Dbal\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Xavier Perseguers <xavier@typo3.org>
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
/**
 * Hooks for TYPO3 Install Tool.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
class InstallToolHooks {

	/**
	 * @var string
	 */
	protected $templateFilePath = 'res/Templates/';

	/**
	 * @var array
	 */
	protected $supportedDrivers;

	/**
	 * @var array
	 */
	protected $availableDrivers;

	/**
	 * @var string
	 */
	protected $driver;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->supportedDrivers = $this->getSupportedDrivers();
		$this->availableDrivers = $this->getAvailableDrivers();
		$configDriver =& $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['handlerCfg']['_DEFAULT']['config']['driver'];
		$this->driver = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('driver');
		if (!$this->driver && $configDriver) {
			$this->driver = $configDriver;
		}
	}

	/**
	 * Hooks into Installer to set required PHP modules.
	 *
	 * @param array $modules
	 * @param \TYPO3\CMS\Install\Installer|\TYPO3\CMS\Reports\Report\Status\SystemStatus $instObj
	 * @return array modules
	 */
	public function setRequiredPhpModules(array $modules, $instObj) {
		$modifiedModules = array();
		foreach ($modules as $key => $module) {
			if ($module === 'mysql') {
				$dbModules = array();
				foreach ($this->supportedDrivers as $abstractionLayer => $drivers) {
					foreach ($drivers as $driver) {
						$dbModules = array_merge($dbModules, $driver['extensions']);
					}
				}
				$module = $dbModules;
			}
			$modifiedModules[] = $module;
		}
		return $modifiedModules;
	}

	/**
	 * Hooks into Installer to let a non-MySQL database to be configured.
	 *
	 * @param array $markers
	 * @param integer $step
	 * @param \TYPO3\CMS\Install\Installer $instObj
	 * @return void
	 */
	public function executeStepOutput(array &$markers, $step, \TYPO3\CMS\Install\Installer $instObj) {
		switch ($step) {
		case 2:
			$this->createConnectionForm($markers, $instObj);
			break;
		case 3:
			$this->createDatabaseForm($markers, $instObj);
			break;
		}
	}

	/**
	 * Hooks into Installer to modify lines to be written to localconf.php.
	 *
	 * @param array $lines This parameter is obsolet as of TYPO3 6.0
	 * @param integer $step
	 * @param \TYPO3\CMS\Install\Installer $instObj
	 * @return void
	 */
	public function executeWriteLocalconf(array &$lines, $step, \TYPO3\CMS\Install\Installer $instObj) {
		switch ($step) {
		case 3:

		case 4:
			$driver = $instObj->INSTALL['Database']['typo_db_driver'];
			if (!$driver && $this->driver) {
				// Driver was already configured
				break;
			}
			$driverConfig = '';
			switch ($driver) {
			case 'oci8':
				$driverConfig = array(
					'driverOptions' => array(
						'connectSID' => $instObj->INSTALL['Database']['typo_db_type'] === 'sid' ? TRUE : FALSE
					)
				);
				break;
			case 'mssql':

			case 'odbc_mssql':
				$driverConfig = array(
					'useNameQuote' => TRUE,
					'quoteClob' => FALSE
				);
				break;
			case 'mysql':
				return;
			}
			$config = array(
				'_DEFAULT' => array(
					'type' => 'adodb',
					'config' => array(
						'driver' => $driver,
						$driverConfig
					)
				)
			);
			\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager')->setLocalConfigurationValuesByPathValuePairs(array('EXTCONF/dbal/handlerCfg' => $config));
			break;
		}
	}

	/**
	 * Creates a specialized form to configure the DBMS connection.
	 *
	 * @param array $markers
	 * @param \TYPO3\CMS\Install\Installer $instObj
	 * @return void
	 */
	protected function createConnectionForm(array &$markers, \TYPO3\CMS\Install\Installer $instObj) {
		// Normalize current driver
		if (!$this->driver) {
			$this->driver = $this->getDefaultDriver();
		}
		// Get the template file
		$templateFile = @file_get_contents((\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dbal') . $this->templateFilePath . 'install.html'));
		// Get the template part from the file
		$template = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($templateFile, '###TEMPLATE###');
		// Get the subpart for the connection form
		$formSubPart = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($template, '###CONNECTION_FORM###');
		if ($this->getNumberOfAvailableDrivers() == 1 && $this->getDefaultDriver() === 'mysql') {
			// Only MySQL is actually available (PDO support may be compiled in
			// PHP itself and as such DBAL was activated, behaves as if DBAL were
			// not activated
			$driverSubPart = '<input type="hidden" name="TYPO3_INSTALL[Database][typo_db_driver]" value="mysql" />';
		} else {
			$driverTemplate = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($formSubPart, '###DATABASE_DRIVER###');
			$driverSubPart = $this->prepareDatabaseDrivers($driverTemplate);
		}
		$formSubPart = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart($formSubPart, '###DATABASE_DRIVER###', $driverSubPart);
		// Get the subpart related to selected database driver
		if ($this->driver === '' || $this->driver === 'mysql') {
			$driverOptionsSubPart = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($template, '###DRIVER_MYSQL###');
		} else {
			$driverOptionsSubPart = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($template, '###DRIVER_' . \TYPO3\CMS\Core\Utility\GeneralUtility::strtoupper($this->driver) . '###');
			if ($driverOptionsSubPart === '') {
				$driverOptionsSubPart = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($template, '###DRIVER_DEFAULT###');
			}
		}
		// Define driver-specific markers
		$driverMarkers = array();
		switch ($this->driver) {
		case 'mssql':
			$driverMarkers = array(
				'labelUsername' => 'Username',
				'username' => TYPO3_db_username,
				'labelPassword' => 'Password',
				'password' => TYPO3_db_password,
				'labelHost' => 'Host',
				'host' => TYPO3_db_host ? TYPO3_db_host : 'windows',
				'labelDatabase' => 'Database',
				'database' => TYPO3_db
			);
			$nextStep = $instObj->step + 2;
			break;
		case 'odbc_mssql':
			$driverMarkers = array(
				'labelUsername' => 'Username',
				'username' => TYPO3_db_username,
				'labelPassword' => 'Password',
				'password' => TYPO3_db_password,
				'labelHost' => 'Host',
				'host' => TYPO3_db_host ? TYPO3_db_host : 'windows',
				'database' => 'dummy_string'
			);
			$nextStep = $instObj->step + 2;
			break;
		case 'oci8':
			$driverMarkers = array(
				'labelUsername' => 'Username',
				'username' => TYPO3_db_username,
				'labelPassword' => 'Password',
				'password' => TYPO3_db_password,
				'labelHost' => 'Host',
				'host' => TYPO3_db_host ? TYPO3_db_host : 'localhost',
				'labelType' => 'Type',
				'labelSID' => 'SID',
				'labelServiceName' => 'Service Name',
				'labelDatabase' => 'Name',
				'database' => TYPO3_db
			);
			$nextStep = $instObj->step + 2;
			break;
		case 'postgres':
			$driverMarkers = array(
				'labelUsername' => 'Username',
				'username' => TYPO3_db_username,
				'labelPassword' => 'Password',
				'password' => TYPO3_db_password,
				'labelHost' => 'Host',
				'host' => TYPO3_db_host ? TYPO3_db_host : 'localhost',
				'labelDatabase' => 'Database',
				'database' => TYPO3_db
			);
			$nextStep = $instObj->step + 2;
			break;
		default:
			$driverMarkers = array(
				'labelUsername' => 'Username',
				'username' => TYPO3_db_username,
				'labelPassword' => 'Password',
				'password' => TYPO3_db_password,
				'labelHost' => 'Host',
				'host' => TYPO3_db_host ? TYPO3_db_host : 'localhost',
				'labelDatabase' => 'Database',
				'database' => TYPO3_db
			);
			$nextStep = $instObj->step + 1;
			break;
		}
		// Add header marker for main template
		$markers['header'] = 'Connect to your database host';
		// Define the markers content for the subpart
		$subPartMarkers = array(
			'step' => $nextStep,
			'action' => htmlspecialchars($instObj->action),
			'encryptionKey' => $instObj->createEncryptionKey(),
			'branch' => TYPO3_branch,
			'driver_options' => $driverOptionsSubPart,
			'continue' => 'Continue',
			'llDescription' => 'If you have not already created a username and password to access the database, please do so now. This can be done using tools provided by your host.'
		);
		$subPartMarkers = array_merge($subPartMarkers, $driverMarkers);
		// Add step marker for main template
		$markers['step'] = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($formSubPart, $subPartMarkers, '###|###', 1, 1);
	}

	/**
	 * Prepares the list of database drivers for step 2.
	 *
	 * @param string $template
	 * @return string
	 */
	protected function prepareDatabaseDrivers($template) {
		$subParts = array(
			'abstractionLayer' => \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($template, '###ABSTRACTION_LAYER###'),
			'vendor' => \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($template, '###VENDOR###')
		);
		// Create the drop-down list of available drivers
		$dropdown = '';
		foreach ($this->availableDrivers as $abstractionLayer => $drivers) {
			$options = array();
			foreach ($drivers as $driver => $label) {
				$markers = array(
					'driver' => $driver,
					'labelvendor' => $label,
					'onclick' => 'document.location=\'index.php?TYPO3_INSTALL[type]=config&mode=123&step=2&driver=' . $driver . '\';',
					'selected' => ''
				);
				if ($driver === $this->driver) {
					$markers['selected'] .= ' selected="selected"';
				}
				$options[] = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($subParts['vendor'], $markers, '###|###', 1);
			}
			$subPart = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart($subParts['abstractionLayer'], '###VENDOR###', implode('
', $options));
			$dropdown .= \TYPO3\CMS\Core\Html\HtmlParser::substituteMarker($subPart, '###LABELABSTRACTIONLAYER###', $abstractionLayer);
		}
		$form = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart($template, '###ABSTRACTION_LAYER###', $dropdown);
		$form = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarker($form, '###LABELDRIVER###', 'Driver');
		return $form;
	}

	/**
	 * Returns a list of DBAL supported database drivers, with a user-friendly name
	 * and any PHP module dependency.
	 *
	 * @return array
	 */
	protected function getSupportedDrivers() {
		$supportedDrivers = array(
			'Native' => array(
				'mysql' => array(
					'label' => 'MySQL/MySQLi (recommended)',
					'combine' => 'OR',
					'extensions' => array('mysql', 'mysqli')
				),
				'mssql' => array(
					'label' => 'Microsoft SQL Server',
					'extensions' => array('mssql')
				),
				'oci8' => array(
					'label' => 'Oracle OCI8',
					'extensions' => array('oci8')
				),
				'postgres' => array(
					'label' => 'PostgreSQL',
					'extensions' => array('pgsql')
				)
			),
			'ODBC' => array(
				'odbc_mssql' => array(
					'label' => 'Microsoft SQL Server',
					'extensions' => array('odbc', 'mssql')
				)
			)
		);
		return $supportedDrivers;
	}

	/**
	 * Returns a list of database drivers that are available on current server.
	 *
	 * @return array
	 */
	protected function getAvailableDrivers() {
		$availableDrivers = array();
		foreach ($this->supportedDrivers as $abstractionLayer => $drivers) {
			foreach ($drivers as $driver => $info) {
				if (isset($info['combine']) && $info['combine'] === 'OR') {
					$isAvailable = FALSE;
				} else {
					$isAvailable = TRUE;
				}
				// Loop through each PHP module dependency to ensure it is loaded
				foreach ($info['extensions'] as $extension) {
					if (isset($info['combine']) && $info['combine'] === 'OR') {
						$isAvailable |= extension_loaded($extension);
					} else {
						$isAvailable &= extension_loaded($extension);
					}
				}
				if ($isAvailable) {
					if (!isset($availableDrivers[$abstractionLayer])) {
						$availableDrivers[$abstractionLayer] = array();
					}
					$availableDrivers[$abstractionLayer][$driver] = $info['label'];
				}
			}
		}
		return $availableDrivers;
	}

	/**
	 * Returns the number of available drivers.
	 *
	 * @return boolean
	 */
	protected function getNumberOfAvailableDrivers() {
		$count = 0;
		foreach ($this->availableDrivers as $drivers) {
			$count += count($drivers);
		}
		return $count;
	}

	/**
	 * Returns the driver that is selected by default in the
	 * Install Tool dropdown list.
	 *
	 * @return string
	 */
	protected function getDefaultDriver() {
		$defaultDriver = '';
		if (count($this->availableDrivers)) {
			$abstractionLayers = array_keys($this->availableDrivers);
			$drivers = array_keys($this->availableDrivers[$abstractionLayers[0]]);
			$defaultDriver = $drivers[0];
		}
		return $defaultDriver;
	}

	/**
	 * Creates a specialized form to configure the database.
	 *
	 * @param array $markers
	 * @param \TYPO3\CMS\Install\Installer $instObj
	 */
	protected function createDatabaseForm(array &$markers, \TYPO3\CMS\Install\Installer $instObj) {
		$error_missingConnect = '
			<p class="typo3-message message-error">
				<strong>
					There is no connection to the database!
				</strong>
				<br />
				(Username: <em>' . TYPO3_db_username . '</em>,
				Host: <em>' . TYPO3_db_host . '</em>,
				Using Password: YES)
				<br />
				Go to Step 1 and enter a valid username and password!
			</p>
		';
		// Add header marker for main template
		$markers['header'] = 'Select database';
		// There should be a database host connection at this point
		if ($result = $GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password)) {
			// Get the template file
			$templateFile = @file_get_contents((\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dbal') . $this->templateFilePath . 'install.html'));
			// Get the template part from the file
			$template = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($templateFile, '###TEMPLATE###');
			// Get the subpart for the database choice step
			$formSubPart = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($template, '###DATABASE_FORM###');
			// Get the subpart for the database options
			$step3DatabaseOptionsSubPart = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($formSubPart, '###DATABASEOPTIONS###');
			$dbArr = $instObj->getDatabaseList();
			$dbIncluded = FALSE;
			foreach ($dbArr as $dbname) {
				// Define the markers content for database options
				$step3DatabaseOptionMarkers = array(
					'databaseValue' => htmlspecialchars($dbname),
					'databaseSelected' => $dbname === TYPO3_db ? 'selected="selected"' : '',
					'databaseName' => htmlspecialchars($dbname)
				);
				// Add the option HTML to an array
				$step3DatabaseOptions[] = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($step3DatabaseOptionsSubPart, $step3DatabaseOptionMarkers, '###|###', 1, 1);
				if ($dbname === TYPO3_db) {
					$dbIncluded = TRUE;
				}
			}
			if (!$dbIncluded && TYPO3_db) {
				// // Define the markers content when no access
				$step3DatabaseOptionMarkers = array(
					'databaseValue' => htmlspecialchars(TYPO3_db),
					'databaseSelected' => 'selected="selected"',
					'databaseName' => htmlspecialchars(TYPO3_db) . ' (NO ACCESS!)'
				);
				// Add the option HTML to an array
				$step3DatabaseOptions[] = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($step3DatabaseOptionsSubPart, $step3DatabaseOptionMarkers, '###|###', 1, 1);
			}
			// Substitute the subpart for the database options
			$content = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart($formSubPart, '###DATABASEOPTIONS###', implode(chr(10), $step3DatabaseOptions));
			// Define the markers content
			$step3SubPartMarkers = array(
				'step' => $instObj->step + 1,
				'action' => htmlspecialchars($instObj->action),
				'llOption2' => 'Select an EMPTY existing database:',
				'llRemark2' => 'Any tables used by TYPO3 will be overwritten.',
				'continue' => 'Continue'
			);
			// Add step marker for main template
			$markers['step'] = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($content, $step3SubPartMarkers, '###|###', 1, 1);
		} else {
			// Add step marker for main template when no connection
			$markers['step'] = $error_missingConnect;
		}
	}

}


?>
