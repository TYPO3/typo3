<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Writer;

/**
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

use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamDirectory;
use \org\bovigo\vfs\vfsStreamWrapper;

/**
 * Test case
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class FileWriterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var string
	 */
	protected $logFileDirectory = 'Log';

	/**
	 * @var string
	 */
	protected $logFileName = 'test.log';

	protected function setUpVfsStream() {
		if (!class_exists('org\\bovigo\\vfs\\vfsStream')) {
			$this->markTestSkipped('File backend tests are not available with this phpunit version.');
		}
		vfsStream::setup('LogRoot');
	}

	/**
	 * Creates a test logger
	 *
	 * @param string $name
	 * @internal param string $component Component key
	 * @return \TYPO3\CMS\Core\Log\Logger
	 */
	protected function createLogger($name = '') {
		if (empty($name)) {
			$name = $this->getUniqueId('test.core.log.');
		}
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->registerLogger($name);
		/** @var \TYPO3\CMS\Core\Log\Logger $logger */
		$logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger($name);
		return $logger;
	}

	/**
	 * Creates a file writer
	 *
	 * @param string $prependName
	 * @return \TYPO3\CMS\Core\Log\Writer\FileWriter
	 */
	protected function createWriter($prependName = '') {
		/** @var \TYPO3\CMS\Core\Log\Writer\FileWriter $writer */
		$writer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter', array(
			'logFile' => $this->getDefaultFileName($prependName)
		));
		return $writer;
	}

	protected function getDefaultFileName($prependName = '') {
		return 'vfs://LogRoot/' . $this->logFileDirectory . '/' . $prependName . $this->logFileName;
	}

	/**
	 * @test
	 */
	public function setLogFileSetsLogFile() {
		$this->setUpVfsStream();
		vfsStream::newFile($this->logFileName)->at(vfsStreamWrapper::getRoot());
		$writer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter');
		$writer->setLogFile($this->getDefaultFileName());
		$this->assertAttributeEquals($this->getDefaultFileName(), 'logFile', $writer);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setLogFileRefusesIllegalPath() {
		$writer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter');
		$writer->setLogFile('/tmp/typo3.log');
	}

	/**
	 * @test
	 */
	public function createsLogFileDirectory() {
		$this->setUpVfsStream();
		$this->createWriter();
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($this->logFileDirectory));
	}

	/**
	 * @test
	 */
	public function createsLogFile() {
		$this->setUpVfsStream();
		$this->createWriter();
		$this->assertTrue(vfsStreamWrapper::getRoot()->getChild($this->logFileDirectory)->hasChild($this->logFileName));
	}

	/**
	 * @return array
	 */
	public function logsToFileDataProvider() {
		$simpleRecord = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogRecord', $this->getUniqueId('test.core.log.fileWriter.simpleRecord.'), \TYPO3\CMS\Core\Log\LogLevel::INFO, 'test record');
		$recordWithData = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogRecord', $this->getUniqueId('test.core.log.fileWriter.recordWithData.'), \TYPO3\CMS\Core\Log\LogLevel::ALERT, 'test record with data', array('foo' => array('bar' => 'baz')));
		return array(
			'simple record' => array($simpleRecord, trim((string) $simpleRecord)),
			'record with data' => array($recordWithData, trim((string) $recordWithData))
		);
	}

	/**
	 * @test
	 * @param \TYPO3\CMS\Core\Log\LogRecord $record Record Test Data
	 * @param string $expectedResult Needle
	 * @dataProvider logsToFileDataProvider
	 */
	public function logsToFile(\TYPO3\CMS\Core\Log\LogRecord $record, $expectedResult) {
		$this->setUpVfsStream();
		$this->createWriter()->writeLog($record);
		$logFileContents = trim(file_get_contents($this->getDefaultFileName()));
		$this->assertEquals($expectedResult, $logFileContents);
	}

	/**
	 * @test
	 * @param \TYPO3\CMS\Core\Log\LogRecord $record Record Test Data
	 * @param string $expectedResult Needle
	 * @dataProvider logsToFileDataProvider
	 */
	public function differentWritersLogToDifferentFiles(\TYPO3\CMS\Core\Log\LogRecord $record, $expectedResult) {
		$this->setUpVfsStream();
		$firstWriter = $this->createWriter();
		$secondWriter = $this->createWriter('second-');

		$firstWriter->writeLog($record);
		$secondWriter->writeLog($record);

		$firstLogFileContents = trim(file_get_contents($this->getDefaultFileName()));
		$secondLogFileContents = trim(file_get_contents($this->getDefaultFileName('second-')));

		$this->assertEquals($expectedResult, $firstLogFileContents);
		$this->assertEquals($expectedResult, $secondLogFileContents);
	}

	/**
	 * @test
	 */
	public function aSecondLogWriterToTheSameFileDoesNotOpenTheFileTwice() {
		$this->setUpVfsStream();

		$firstWriter = $this->getMock('TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter', array('dummy'));
		$secondWriter = $this->getMock('TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter', array('createLogFile'));

		$secondWriter->expects($this->never())->method('createLogFile');

		$logFilePrefix = $this->getUniqueId('unique');
		$firstWriter->setLogFile($this->getDefaultFileName($logFilePrefix));
		$secondWriter->setLogFile($this->getDefaultFileName($logFilePrefix));

	}

	/**
	 * @test
	 */
	public function createsHtaccessForNewDirectory() {
		$this->setUpVfsStream();
		$directory = $this->getUniqueId('Log');
		$logFile = 'vfs://LogRoot/' . $directory . '/' . $this->logFileName;
		$this->createWriter()->setLogFile($logFile);
		$this->assertFileExists('vfs://LogRoot/' . $directory . '/.htaccess');
	}

	/**
	 * @test
	 */
	public function createsNoHtaccessForExistingDirectory() {
		$this->setUpVfsStream();
		$directory = $this->getUniqueId('Log');
			// create a directory
		vfsStreamWrapper::getRoot()->addChild(new vfsStreamDirectory($directory));
		$logFile = 'vfs://LogRoot/' . $directory . '/' . $this->logFileName;
		$this->assertTrue(is_dir('vfs://LogRoot/' . $directory));
		$this->createWriter()->setLogFile($logFile);
		$this->assertFileNotExists('vfs://LogRoot/' . $directory . '/.htaccess');
	}

}
