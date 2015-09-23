<?php
namespace TYPO3\CMS\Jumpurl\Tests\Unit;

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

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Jumpurl\JumpUrlHandler;
use TYPO3\CMS\Jumpurl\JumpUrlProcessor;

/**
 * Testcase for the jumpurl processing in TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer.
 */
class ContentObjectRendererTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $subject = NULL;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\TypoScript\TemplateService
	 */
	protected $templateServiceMock = NULL;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $typoScriptFrontendControllerMock = NULL;

	/**
	 * Set up
	 */
	protected function setUp() {
		$this->singletonInstances = GeneralUtility::getSingletonInstances();
		$this->createMockedLoggerAndLogManager();

		$this->templateServiceMock = $this->getMock(TemplateService::class, array('getFileName'));
		$pageRepositoryMock = $this->getMock(PageRepository::class, array('getPage'));

		$this->typoScriptFrontendControllerMock = $this->getAccessibleMock(TypoScriptFrontendController::class, array('dummy'), array(), '', FALSE);
		$this->typoScriptFrontendControllerMock->tmpl = $this->templateServiceMock;
		$this->typoScriptFrontendControllerMock->config = array();
		$this->typoScriptFrontendControllerMock->page = array();
		$this->typoScriptFrontendControllerMock->sys_page = $pageRepositoryMock;
		$this->typoScriptFrontendControllerMock->csConvObj = new CharsetConverter();
		$this->typoScriptFrontendControllerMock->renderCharset = 'utf-8';
		$GLOBALS['TSFE'] = $this->typoScriptFrontendControllerMock;

		$GLOBALS['TT'] = $this->getMock(TimeTracker::class, array('dummy'));

		$GLOBALS['TYPO3_DB'] = $this->getMock(DatabaseConnection::class, array());
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';

		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlProcessors']['jumpurl']['processor'] = JumpUrlProcessor::class;
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlHandlers']['jumpurl']['handler'] = JumpUrlHandler::class;

		$this->subject = $this->getAccessibleMock(
			ContentObjectRenderer::class,
			array('getResourceFactory', 'getEnvironmentVariable'),
			array($this->typoScriptFrontendControllerMock)
		);
		$this->subject->start(array(), 'tt_content');
	}

	protected function tearDown() {
		GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function filelinkCreatesCorrectJumpUrlSecureForFileWithUrlEncodedSpecialChars() {

		$testData = $this->initializeJumpUrlTestEnvironment($this->once());

		$fileNameAndPath = PATH_site . 'typo3temp/phpunitJumpUrlTestFile with spaces & amps.txt';
		file_put_contents($fileNameAndPath, 'Some test data');
		$relativeFileNameAndPath = substr($fileNameAndPath, strlen(PATH_site));
		$fileName = substr($fileNameAndPath, strlen(PATH_site . 'typo3temp/'));

		$expectedHash = '304b8c8e022e92e6f4d34e97395da77705830818';
		$expectedLink = htmlspecialchars($testData['absRefPrefix'] . $testData['mainScript'] . '?id=' . $testData['pageId'] . '&type=' . $testData['pageType'] . '&jumpurl=' . rawurlencode(str_replace('%2F', '/', rawurlencode($relativeFileNameAndPath))) . '&juSecure=1&locationData=' . rawurlencode($testData['locationData']) . '&juHash=' . $expectedHash);

		$result = $this->subject->filelink($fileName, array('path' => 'typo3temp/', 'jumpurl' => '1', 'jumpurl.' => array('secure' => 1)));
		$this->assertEquals('<a href="' . $expectedLink . '">' . $fileName . '</a>', $result);

		GeneralUtility::unlink_tempfile($fileNameAndPath);
	}

	/**
	 * @test
	 */
	public function filelinkCreatesCorrectSecureJumpUrlIfConfigured() {

		$testData = $this->initializeJumpUrlTestEnvironment($this->once());

		$fileNameAndPath = PATH_site . 'typo3temp/phpunitJumpUrlTestFile.txt';
		file_put_contents($fileNameAndPath, 'Some test data');
		$relativeFileNameAndPath = substr($fileNameAndPath, strlen(PATH_site));
		$fileName = substr($fileNameAndPath, strlen(PATH_site . 'typo3temp/'));

		$expectedHash = '1933f3c181db8940acfcd4d16c74643947179948';
		$expectedLink = htmlspecialchars($testData['absRefPrefix'] . $testData['mainScript'] . '?id=' . $testData['pageId'] . '&type=' . $testData['pageType'] . '&jumpurl=' . rawurlencode($relativeFileNameAndPath) . '&juSecure=1&locationData=' . rawurlencode($testData['locationData']) . '&juHash=' . $expectedHash);

		$result = $this->subject->filelink($fileName, array('path' => 'typo3temp/', 'jumpurl' => '1', 'jumpurl.' => array('secure' => 1)));
		$this->assertEquals('<a href="' . $expectedLink . '">' . $fileName . '</a>', $result);

		GeneralUtility::unlink_tempfile($fileNameAndPath);
	}

	/**
	 * @test
	 */
	public function filelinkDisablesGlobalJumpUrlIfConfigured() {

		$testData = $this->initializeJumpUrlTestEnvironment($this->never());

		$fileName = 'phpunitJumpUrlTestFile.txt';
		$fileNameAndPath = 'typo3temp/' . $fileName;
		file_put_contents(PATH_site . $fileNameAndPath, 'Some test data');

		$expectedLink = $testData['absRefPrefix'] . $fileNameAndPath;
		$expectedLink = '<a href="' . $expectedLink . '">' . $fileName . '</a>';

		// Test with deprecated configuration, TODO: remove when deprecated code is removed!
		$result = $this->subject->filelink($fileName, array('path' => 'typo3temp/', 'jumpurl' => 0));
		$this->assertEquals($expectedLink, $result);

		GeneralUtility::unlink_tempfile($fileNameAndPath);
	}

	/**
	 * @test
	 */
	public function filelinkDisablesGlobalJumpUrlWithDeprecatedOptionIfConfigured() {

		$testData = $this->initializeJumpUrlTestEnvironment($this->never());

		$fileName = 'phpunitJumpUrlTestFile.txt';
		$fileNameAndPath = 'typo3temp/' . $fileName;
		file_put_contents(PATH_site . $fileNameAndPath, 'Some test data');

		$expectedLink = $testData['absRefPrefix'] . $fileNameAndPath;
		$expectedLink = '<a href="' . $expectedLink . '">' . $fileName . '</a>';

		// Test with deprecated configuration
		$result = $this->subject->filelink($fileName, array('path' => 'typo3temp/', 'jumpurl' => 0));
		$this->assertEquals($expectedLink, $result);

		GeneralUtility::unlink_tempfile($fileNameAndPath);
	}

	/**
	 * @test
	 */
	public function makeHttpLinksCreatesCorrectJumpUrlIfConfigured() {

		$testData = $this->initializeJumpUrlTestEnvironment();

		$testUrl = 'http://www.mytesturl.tld';
		$expectedHash = '7d2261b12682a4b73402ae67415e09f294b29a55';

		$expectedLinkFirstPart = $testData['absRefPrefix'] . $testData['mainScript'] . '?id=' . $testData['pageId'] . '&type=' . $testData['pageType'] . '&jumpurl=' . rawurlencode($testUrl);
		$expectedLinkSecondPart = '&juHash=' . $expectedHash;

		// due to a bug in the jump URL generation in the old version only
		// the first part of the link is encoded which does not make much sense.
		$expectedLink = htmlspecialchars($expectedLinkFirstPart . $expectedLinkSecondPart);

		$result = $this->subject->http_makelinks('teststring ' . $testUrl . ' anotherstring', array('keep' => 'scheme'));
		$this->assertEquals('teststring <a href="' . $expectedLink . '">' . $testUrl . '</a> anotherstring', $result);
	}

	/**
	 * @test
	 */
	public function makeMailtoLinksCreatesCorrectJumpUrlIfConfigured() {

		$testData = $this->initializeJumpUrlTestEnvironment();

		$testMail = 'mail@ddress.tld';
		$testMailto = 'mailto:' . $testMail;
		$expectedHash = 'bd82328dc40755f5d0411e2e16e7c0cbf33b51b7';
		$expectedLink = htmlspecialchars($testData['absRefPrefix'] . $testData['mainScript'] . '?id=' . $testData['pageId'] . '&type=' . $testData['pageType'] . '&jumpurl=' . rawurlencode($testMailto) . '&juHash=' . $expectedHash);
		$result = $this->subject->mailto_makelinks('teststring ' . $testMailto . ' anotherstring', array());

		$this->assertEquals('teststring <a href="' . $expectedLink . '">' . $testMail . '</a> anotherstring', $result);
	}

	/**
	 * @test
	 */
	public function typoLinkCreatesCorrectJumpUrlForExternalUrl() {

		$testData = $this->initializeJumpUrlTestEnvironment();

		$testAddress = 'http://external.domain.tld';
		$expectedHash = '8591c573601d17f37e06aff4ac14c78f107dd49e';
		$expectedUrl = $testData['absRefPrefix'] . $testData['mainScript'] . '?id=' . $testData['pageId'] . '&type=' . $testData['pageType'] . '&jumpurl=' . rawurlencode($testAddress) . '&juHash=' . $expectedHash;
		$generatedUrl = $this->subject->typoLink_URL(array('parameter' => $testAddress));

		$this->assertEquals($expectedUrl, $generatedUrl);
	}

	/**
	 * @test
	 */
	public function typoLinkCreatesCorrectJumpUrlForExternalUrlWithUrlEncodedParameters() {

		$testData = $this->initializeJumpUrlTestEnvironment();

		$testAddress = 'http://external.domain.tld?parameter1=' . rawurlencode('parameter[data]with&a lot-of-special/chars');
		$expectedHash = 'cfc95f583da7689238e98bbc8930ebd820f0d20f';
		$expectedUrl = $testData['absRefPrefix'] . $testData['mainScript'] . '?id=' . $testData['pageId'] . '&type=' . $testData['pageType'] . '&jumpurl=' . rawurlencode($testAddress) . '&juHash=' . $expectedHash;
		$generatedUrl = $this->subject->typoLink_URL(array('parameter' => $testAddress));

		$this->assertEquals($expectedUrl, $generatedUrl);
	}

	/**
	 * @test
	 */
	public function typoLinkCreatesCorrectJumpUrlForFile() {

		$testData = $this->initializeJumpUrlTestEnvironment();

		$fileNameAndPath = PATH_site . 'typo3temp/phpunitJumpUrlTestFile.txt';
		file_put_contents($fileNameAndPath, 'Some test data');
		$relativeFileNameAndPath = substr($fileNameAndPath, strlen(PATH_site));

		$testAddress = $relativeFileNameAndPath;
		$expectedHash = 'e36be153c32f4d4d0db1414e47a05cf3149923ae';
		$expectedUrl = $testData['absRefPrefix'] . $testData['mainScript'] . '?id=' . $testData['pageId'] . '&type=' . $testData['pageType'] . '&jumpurl=' . rawurlencode($testAddress) . '&juHash=' . $expectedHash;
		$generatedUrl = $this->subject->typoLink_URL(array('parameter' => $testAddress));

		$this->assertEquals($expectedUrl, $generatedUrl);

		GeneralUtility::unlink_tempfile($fileNameAndPath);
	}

	/**
	 * @test
	 */
	public function typoLinkCreatesCorrectJumpUrlForFileWithSpecialUrlEncodedSpecialChars() {

		$testData = $this->initializeJumpUrlTestEnvironment();

		$fileNameAndPath = PATH_site . 'typo3temp/phpunitJumpUrlTestFile with spaces & amps.txt';
		file_put_contents($fileNameAndPath, 'Some test data');
		$relativeFileNameAndPath = substr($fileNameAndPath, strlen(PATH_site));

		$testFileLink = $relativeFileNameAndPath;
		$expectedHash = '691dbf63a21181e2d69bf78e61f1c9fd023aef2c';
		$expectedUrl = $testData['absRefPrefix'] . $testData['mainScript'] . '?id=' . $testData['pageId'] . '&type=' . $testData['pageType'] . '&jumpurl=' . rawurlencode(str_replace('%2F', '/', rawurlencode($testFileLink))) . '&juHash=' . $expectedHash;
		$generatedUrl = $this->subject->typoLink_URL(array('parameter' => str_replace('%2F', '/', rawurlencode($testFileLink))));

		$this->assertEquals($expectedUrl, $generatedUrl);

		GeneralUtility::unlink_tempfile($fileNameAndPath);
	}

	/**
	 * @test
	 */
	public function typoLinkCreatesCorrectJumpUrlForFileWithUrlEncodedSpecialChars() {

		$testData = $this->initializeJumpUrlTestEnvironment();

		$fileNameAndPath = PATH_site . 'typo3temp/phpunitJumpUrlTestFile with spaces & amps.txt';
		file_put_contents($fileNameAndPath, 'Some test data');
		$relativeFileNameAndPath = substr($fileNameAndPath, strlen(PATH_site));

		$testFileLink = $relativeFileNameAndPath;
		$expectedHash = '691dbf63a21181e2d69bf78e61f1c9fd023aef2c';
		$expectedUrl = $testData['absRefPrefix'] . $testData['mainScript'] . '?id=' . $testData['pageId'] . '&type=' . $testData['pageType'] . '&jumpurl=' . rawurlencode(str_replace('%2F', '/', rawurlencode($testFileLink))) . '&juHash=' . $expectedHash;
		$generatedUrl = $this->subject->typoLink_URL(array('parameter' => rawurlencode($testFileLink)));

		$this->assertEquals($expectedUrl, $generatedUrl);

		GeneralUtility::unlink_tempfile($fileNameAndPath);
	}

	/**
	 * @test
	 */
	public function typoLinkCreatesCorrectJumpUrlForMail() {

		$testData = $this->initializeJumpUrlTestEnvironment();

		$testAddress = 'mail@ddress.tld';
		$expectedHash = 'bd82328dc40755f5d0411e2e16e7c0cbf33b51b7';
		$expectedUrl = $testData['absRefPrefix'] . $testData['mainScript'] . '?id=' . $testData['pageId'] . '&type=' . $testData['pageType'] . '&jumpurl=' . rawurlencode('mailto:' . $testAddress) . '&juHash=' . $expectedHash;
		$generatedUrl = $this->subject->typoLink_URL(array('parameter' => $testAddress));

		$this->assertEquals($expectedUrl, $generatedUrl);
	}

	/**
	 * @test
	 */
	public function typoLinkCreatesCorrectSecureJumpUrlForFile() {

		$testData = $this->initializeJumpUrlTestEnvironment();

		$fileNameAndPath = PATH_site . 'typo3temp/phpunitJumpUrlTestFile.txt';
		file_put_contents($fileNameAndPath, 'Some test data');
		$relativeFileNameAndPath = substr($fileNameAndPath, strlen(PATH_site));

		$testAddress = $relativeFileNameAndPath;
		$expectedHash = '1933f3c181db8940acfcd4d16c74643947179948';
		$expectedUrl = $testData['absRefPrefix'] . $testData['mainScript'] . '?id=' . $testData['pageId'] . '&type=' . $testData['pageType'] . '&jumpurl=' . rawurlencode($testAddress) . '&juSecure=1&locationData=' . rawurlencode($testData['locationData']) . '&juHash=' . $expectedHash;
		$generatedUrl = $this->subject->typoLink_URL(array('parameter' => $testAddress, 'jumpurl.' => array('secure' => 1)));

		$this->assertEquals($expectedUrl, $generatedUrl);

		GeneralUtility::unlink_tempfile($fileNameAndPath);
	}

	/**
	 * Avoid logging to the file system (file writer is currently the only configured writer)
	 */
	protected function createMockedLoggerAndLogManager() {
		/** @var \TYPO3\CMS\Core\SingletonInterface $logManagerMock */
		$logManagerMock = $this->getMock(LogManager::class);
		$loggerMock = $this->getMock(LoggerInterface::class);
		$logManagerMock->expects($this->any())
			->method('getLogger')
			->willReturn($loggerMock);
		GeneralUtility::setSingletonInstance(LogManager::class, $logManagerMock);
	}

	/**
	 * Initializes all required settings in $GLOBALS['TSFE'] and the current
	 * content object renderer for testing jump URL functionality.
	 *
	 * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expectedGetPageCalls
	 * @return array
	 */
	protected function initializeJumpUrlTestEnvironment($expectedGetPageCalls = NULL) {

		if (!isset($expectedGetPageCalls)) {
			$expectedGetPageCalls = $this->once();
		}

		$testData = array();

		$this->typoScriptFrontendControllerMock->config['config']['jumpurl_enable'] = TRUE;

		$testData['pageId'] = $this->typoScriptFrontendControllerMock->id = '1234';
		$testData['pageType'] = $this->typoScriptFrontendControllerMock->type = '4';
		$testData['mainScript'] = $this->typoScriptFrontendControllerMock->config['mainScript'] = 'index.php';
		$testData['absRefPrefix'] = $this->typoScriptFrontendControllerMock->absRefPrefix = '/prefix/';
		$this->subject->currentRecord = 'tt_content:999';
		$testData['locationData'] = $testData['pageId'] . ':' . $this->subject->currentRecord;

		/** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\Page\PageRepository $pageRepositoryMock */
		$pageRepositoryMock = $this->typoScriptFrontendControllerMock->sys_page;
		$pageRepositoryMock->expects($expectedGetPageCalls)
			->method('getPage')
			->will($this->returnValue(
				array(
					'uid' => $testData['pageId'],
					'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT,
					'url_scheme' => 0,
					'title' => 'testpage',
				)
			)
			);

		return $testData;
	}
}
