<?php
/***************************************************************
* Copyright notice
*
* (c) 2011-2012 Steffen Gebert (steffen.gebert@typo3.org)
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


/**
 * Testcase for t3lib_log_writer_File
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class t3lib_log_writer_FileTest extends tx_phpunit_testcase {

	protected $logFileDirectory = 'Log';
	protected $logFileName = 'test.log';


	protected function setUpVfsStream() {
		if (!class_exists('vfsStream')) {
			$this->markTestSkipped('File backend tests are not available with this phpunit version.');
		}

		vfsStream::setup("LogRoot");
	}

	/**
	 * Creates a test logger
	 *
	 * @param string $component Component key
	 * @return t3lib_log_Logger
	 */
	protected function createLogger($name = '') {
		if (empty($name)) {
			$name = uniqid('test.core.log.');
		}

		t3lib_log_LogManager::registerLogger($name);

		/** @var t3lib_log_Logger $logger */
		$logger = t3lib_log_LogManager::getLogger($name);

		return $logger;
	}

	/**
	 * Creates a file writer
	 *
	 * @return t3lib_log_writer_File
	 */
	protected function createWriter() {
		/** @var t3lib_log_writer_File $writer */
		$writer = t3lib_div::makeInstance('t3lib_log_writer_File', array(
			'logFile' => 'vfs://LogRoot/' . $this->logFileDirectory . '/' . $this->logFileName
		));

		return $writer;
	}

	protected function getDefaultFileName() {
		return 'vfs://LogRoot/' . $this->logFileDirectory . '/' . $this->logFileName;
	}



	/**
	 * @test
	 */
	public function setLogFileSetsLogFile() {
		$this->setUpVfsStream();
		vfsStream::newFile($this->logFileName)->at(vfsStreamWrapper::getRoot());

		$writer = t3lib_div::makeInstance('t3lib_log_writer_File');
		$writer->setLogFile($this->getDefaultFileName());

		$this->assertAttributeEquals($this->getDefaultFileName(), 'logFile', $writer);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setLogFileRefusesIllegalPath() {
		$this->createWriter()->setLogFile('/tmp/typo3.log');
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
		$simpleRecord = t3lib_div::makeInstance('t3lib_log_Record',
			uniqid('test.core.log.fileWriter.simpleRecord.'),
			t3lib_log_Level::INFO,
			'test record'
		);

		$recordWithData = t3lib_div::makeInstance('t3lib_log_Record',
			uniqid('test.core.log.fileWriter.recordWithData.'),
			t3lib_log_Level::ALERT,
			'test record with data',
			array('foo' => array('bar' => 'baz'))
		);

		return array(
			'simple record'    => array($simpleRecord,   (string) $simpleRecord),
			'record with data' => array($recordWithData, (string) $recordWithData),
		);
	}

	/**
	 * @test
	 * @param t3lib_log_Record $record Record Test Data
	 * @param string $expectedResult Needle
	 * @dataProvider logsToFileDataProvider
	 */
	public function logsToFile(t3lib_log_Record $record, $expectedResult) {

		$this->setUpVfsStream();

		$this->createWriter()->writeLog($record);

		$logFileContents = file_get_contents($this->getDefaultFileName());
		$logFileContents = trim($logFileContents);
		$expectedResult = trim($expectedResult);

		$this->assertEquals($logFileContents, $expectedResult);
	}

	/**
	 * @test
	 */
	public function createsHtaccessForNewDirectory() {
		$this->setUpVfsStream();
		$directory = uniqid('Log');

		$logFile = 'vfs://LogRoot/' . $directory . '/' . $this->logFileName;
		$this->createWriter()->setLogFile($logFile);

		$this->assertFileExists('vfs://LogRoot/' . $directory . '/.htaccess');
	}

	/**
	 * @test
	 */
	public function createsNoHtaccessForExistingDirectory() {
		$this->setUpVfsStream();
		$directory = uniqid('Log');
			// create a directory
		vfsStreamWrapper::getRoot()->addChild(new vfsStreamDirectory($directory));

		$logFile = 'vfs://LogRoot/' . $directory . '/' . $this->logFileName;
		$this->assertTrue(is_dir('vfs://LogRoot/' . $directory));

		$this->createWriter()->setLogFile($logFile);

		$this->assertFileNotExists('vfs://LogRoot/' . $directory . '/.htaccess');
	}
}

?>