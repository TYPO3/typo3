<?php
namespace TYPO3\CMS\Core\Tests;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use \TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\Response;

/**
 * Base test case class for functional tests, all TYPO3 CMS
 * functional tests should extend from this class!
 *
 * If functional tests need additional setUp() and tearDown() code,
 * they *must* call parent::setUp() and parent::tearDown() to properly
 * set up and destroy the test system.
 *
 * The functional test system creates a full new TYPO3 CMS instance
 * within typo3temp/ of the base system and the bootstraps this TYPO3 instance.
 * This abstract class takes care of creating this instance with its
 * folder structure and a LocalConfiguration, creates an own database
 * for each test run and imports tables of loaded extensions.
 *
 * Functional tests must be run standalone (calling native phpunit
 * directly) and can not be executed by eg. the ext:phpunit backend module.
 * Additionally, the script must be called from the document root
 * of the instance, otherwise path calculation is not successfully.
 *
 * Call whole functional test suite, example:
 * - cd /var/www/t3master/foo  # Document root of CMS instance, here is index.php of frontend
 * - ./typo3conf/ext/phpunit/Composer/vendor/bin/phpunit -c typo3/sysext/core/Build/FunctionalTests.xml
 *
 * Call single test case, example:
 * - cd /var/www/t3master/foo  # Document root of CMS instance, here is index.php of frontend
 * - ./typo3conf/ext/phpunit/Composer/vendor/bin/phpunit \
 *     --process-isolation \
 *     --bootstrap typo3/sysext/core/Build/FunctionalTestsBootstrap.php \
 *     typo3/sysext/core/Tests/Functional/DataHandling/DataHandlerTest.php
 */
abstract class FunctionalTestCase extends BaseTestCase {
	/**
	 * Core extensions to load.
	 *
	 * If the test case needs additional core extensions as requirement,
	 * they can be noted here and will be added to LocalConfiguration
	 * extension list and ext_tables.sql of those extensions will be applied.
	 *
	 * This property will stay empty in this abstract, so it is possible
	 * to just overwrite it in extending classes. Extensions noted here will
	 * be loaded for every test of a test case and it is not possible to change
	 * the list of loaded extensions between single tests of a test case.
	 *
	 * A default list of core extensions is always loaded.
	 *
	 * @see FunctionalTestCaseUtility $defaultActivatedCoreExtensions
	 * @var array
	 */
	protected $coreExtensionsToLoad = array();

	/**
	 * Array of test/fixture extensions paths that should be loaded for a test.
	 *
	 * This property will stay empty in this abstract, so it is possible
	 * to just overwrite it in extending classes. Extensions noted here will
	 * be loaded for every test of a test case and it is not possible to change
	 * the list of loaded extensions between single tests of a test case.
	 *
	 * Given path is expected to be relative to your document root, example:
	 *
	 * array(
	 *   'typo3conf/ext/some_extension/Tests/Functional/Fixtures/Extensions/test_extension',
	 *   'typo3conf/ext/base_extension',
	 * );
	 *
	 * Extensions in this array are linked to the test instance, loaded
	 * and their ext_tables.sql will be applied.
	 *
	 * @var array
	 */
	protected $testExtensionsToLoad = array();

	/**
	 * Array of test/fixture folder or file paths that should be linked for a test.
	 *
	 * This property will stay empty in this abstract, so it is possible
	 * to just overwrite it in extending classes. Path noted here will
	 * be linked for every test of a test case and it is not possible to change
	 * the list of folders between single tests of a test case.
	 *
	 * array(
	 *   'link-source' => 'link-destination'
	 * );
	 *
	 * Given paths are expected to be relative to the test instance root.
	 * The array keys are the source paths and the array values are the destination
	 * paths, example:
	 *
	 * array(
	 *   'typo3/sysext/impext/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' =>
	 *   'fileadmin/user_upload',
	 *   'typo3conf/ext/my_own_ext/Tests/Functional/Fixtures/Folders/uploads/tx_myownext' =>
	 *   'uploads/tx_myownext'
	 * );
	 *
	 * To be able to link from my_own_ext the extension path needs also to be registered in
	 * property $testExtensionsToLoad
	 *
	 * @var array
	 */
	protected $pathsToLinkInTestInstance = array();

	/**
	 * This configuration array is merged with TYPO3_CONF_VARS
	 * that are set in default configuration and factory configuration
	 *
	 * @var array
	 */
	protected $configurationToUseInTestInstance = array();

	/**
	 * Array of folders that should be created inside the test instance document root.
	 *
	 * This property will stay empty in this abstract, so it is possible
	 * to just overwrite it in extending classes. Path noted here will
	 * be linked for every test of a test case and it is not possible to change
	 * the list of folders between single tests of a test case.
	 *
	 * Per default the following folder are created
	 * /fileadmin
	 * /typo3temp
	 * /typo3conf
	 * /typo3conf/ext
	 * /uploads
	 *
	 * To create additional folders add the paths to this array. Given paths are expected to be
	 * relative to the test instance root and have to begin with a slash. Example:
	 *
	 * array(
	 *   'fileadmin/user_upload'
	 * );
	 *
	 * @var array
	 */
	protected $additionalFoldersToCreate = array();

	/**
	 * Private utility class used in setUp() and tearDown(). Do NOT use in test cases!
	 *
	 * @var \TYPO3\CMS\Core\Tests\FunctionalTestCaseBootstrapUtility
	 */
	private $bootstrapUtility = NULL;

	/**
	 * Path to TYPO3 CMS test installation for this test case
	 *
	 * @var string
	 */
	private $instancePath;

	/**
	 * Set up creates a test instance and database.
	 *
	 * This method should be called with parent::setUp() in your test cases!
	 *
	 * @return void
	 */
	public function setUp() {
		if (!defined('ORIGINAL_ROOT')) {
			$this->markTestSkipped('Functional tests must be called through phpunit on CLI');
		}
		$this->bootstrapUtility = new FunctionalTestCaseBootstrapUtility();
		$this->instancePath = $this->bootstrapUtility->setUp(
			get_class($this),
			$this->coreExtensionsToLoad,
			$this->testExtensionsToLoad,
			$this->pathsToLinkInTestInstance,
			$this->configurationToUseInTestInstance,
			$this->additionalFoldersToCreate
		);
	}

	/**
	 * Get DatabaseConnection instance - $GLOBALS['TYPO3_DB']
	 *
	 * This method should be used instead of direct access to
	 * $GLOBALS['TYPO3_DB'] for easy IDE auto completion.
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Initialize backend user
	 *
	 * @param int $userUid uid of the user we want to initialize. This user must exist in the fixture file
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 * @throws Exception
	 */
	protected function setUpBackendUserFromFixture($userUid) {
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/be_users.xml');
		$database = $this->getDatabaseConnection();
		$userRow = $database->exec_SELECTgetSingleRow('*', 'be_users', 'uid = ' . $userUid);

		/** @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
		$backendUser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$sessionId = $backendUser->createSessionId();
		$_SERVER['HTTP_COOKIE'] = 'be_typo_user=' . $sessionId . '; path=/';
		$backendUser->id = $sessionId;
		$backendUser->sendNoCacheHeaders = FALSE;
		$backendUser->dontSetCookie = TRUE;
		$backendUser->createUserSession($userRow);

		$GLOBALS['BE_USER'] = $backendUser;
		$GLOBALS['BE_USER']->start();
		if (!is_array($GLOBALS['BE_USER']->user) || !$GLOBALS['BE_USER']->user['uid']) {
			throw new Exception(
				'Can not initialize backend user',
				1377095807
			);
		}
		$GLOBALS['BE_USER']->backendCheckLogin();

		return $backendUser;
	}

	/**
	 * Imports a data set represented as XML into the test database,
	 *
	 * @param string $path Absolute path to the XML file containing the data set to load
	 * @return void
	 * @throws Exception
	 */
	protected function importDataSet($path) {
		if (!is_file($path)) {
			throw new Exception(
				'Fixture file ' . $path . ' not found',
				1376746261
			);
		}

		$database = $this->getDatabaseConnection();

		$xml = simplexml_load_file($path);
		$foreignKeys = array();

		/** @var $table \SimpleXMLElement */
		foreach ($xml->children() as $table) {
			$insertArray = array();

			/** @var $column \SimpleXMLElement */
			foreach ($table->children() as $column) {
				$columnName = $column->getName();
				$columnValue = NULL;

				if (isset($column['ref'])) {
					list($tableName, $elementId) = explode('#', $column['ref']);
					$columnValue = $foreignKeys[$tableName][$elementId];
				} elseif (isset($column['is-NULL']) && ($column['is-NULL'] === 'yes')) {
					$columnValue = NULL;
				} else {
					$columnValue = (string) $table->$columnName;
				}

				$insertArray[$columnName] = $columnValue;
			}

			$tableName = $table->getName();
			$result = $database->exec_INSERTquery($tableName, $insertArray);
			if ($result === FALSE) {
				throw new Exception(
					'Error when processing fixture file: ' . $path . ' Can not insert data to table ' . $tableName,
					1376746262
				);
			}
			if (isset($table['id'])) {
				$elementId = (string) $table['id'];
				$foreignKeys[$tableName][$elementId] = $database->sql_insert_id();
			}
		}
	}

	/**
	 * @param int $pageId
	 * @param array $typoScriptFiles
	 */
	protected function setUpFrontendRootPage($pageId, array $typoScriptFiles = array()) {
		$pageId = (int)$pageId;
		$page = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'pages', 'uid=' . $pageId);

		if (empty($page)) {
			$this->fail('Cannot set up frontend root page "' . $pageId . '"');
		}

		$pagesFields = array(
			'is_siteroot' => 1
		);

		$this->getDatabaseConnection()->exec_UPDATEquery('pages', 'uid=' . $pageId, $pagesFields);

		$templateFields = array(
			'pid' => $pageId,
			'title' => '',
			'config' => '',
			'clear' => 3,
			'root' => 1,
		);

		foreach ($typoScriptFiles as $typoScriptFile) {
			$templateFields['config'] .= '<INCLUDE_TYPOSCRIPT: source="FILE:' . $typoScriptFile . '">' . LF;
		}

		$this->getDatabaseConnection()->exec_INSERTquery('sys_template', $templateFields);
	}

	/**
	 * @param int $pageId
	 * @param int $languageId
	 * @param int $backendUserId
	 * @param int $workspaceId
	 * @param bool $failOnFailure
	 * @return Response
	 */
	protected function getFrontendResponse($pageId, $languageId = 0, $backendUserId = 0, $workspaceId = 0, $failOnFailure = TRUE) {
		$pageId = (int)$pageId;
		$languageId = (int)$languageId;

		$phpExecutable = 'php';
		if (defined('PHP_BINARY')) {
			$phpExecutable = PHP_BINARY;
		} elseif (TYPO3_OS !== 'WIN' && defined('PHP_BINDIR')) {
			$phpExecutable = rtrim(PHP_BINDIR, '/') . '/php';
		} else {
			foreach(explode(';', $_SERVER['Path']) as $path) {
				$path = rtrim(strtr($path, '\\', '/'), '/') . '/';
				$phpFile = 'php' . (TYPO3_OS === 'WIN' ? '.exe' : '');
				if (file_exists($path . $phpFile) && is_file($path . $phpFile)) {
					$phpExecutable = $path . $phpFile;
					break;
				}
			}
		}

		$additionalParameter = '';

		if (!empty($backendUserId)) {
			$additionalParameter .= '&backendUserId=' . (int)$backendUserId;
		}
		if (!empty($workspaceId)) {
			$additionalParameter .= '&workspaceId=' . (int)$workspaceId;
		}

		$arguments = array(
			'documentRoot' => $this->instancePath,
			'requestUrl' => 'http://localhost/?id=' . $pageId . '&L=' . $languageId . $additionalParameter,
		);

		if (TYPO3_OS !== 'WIN') {
			$commandParts = array(
				escapeshellcmd($phpExecutable),
				escapeshellarg(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Framework/Scripts/Request.php'),
				escapeshellarg(json_encode($arguments)),
			);
		} else {
			$commandParts = array(
				escapeshellcmd($phpExecutable),
				escapeshellarg(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Framework/Scripts/Request.php'),
				strtr(escapeshellarg(strtr(json_encode($arguments), array('&' => '^&', '"' => '"""'))), '   ', '"""'),
			);
		}

		$command = trim(implode(' ', $commandParts));
		$response = shell_exec($command);
		$result = json_decode($response, TRUE);

		if ($result === FALSE) {
			$this->fail('Frontend Response is empty');
		}

		if ($failOnFailure && $result['status'] === Response::STATUS_Failure) {
			$this->fail('Frontend Response has failure:' . LF . $result['error']);
		}

		$response = new Response($result['status'], $result['content'], $result['error']);
		return $response;
	}

}
