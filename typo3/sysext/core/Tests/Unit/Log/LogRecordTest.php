<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log;

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

/**
 * Test case
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class LogRecordTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Returns a \TYPO3\CMS\Core\Log\LogRecord
	 *
	 * @param array $parameters Parameters to set in \TYPO3\CMS\Core\Log\LogRecord constructor.
	 * @return \TYPO3\CMS\Core\Log\LogRecord
	 */
	protected function getRecord(array $parameters = array()) {
		/** @var $record \TYPO3\CMS\Core\Log\LogRecord */
		$record = new \TYPO3\CMS\Core\Log\LogRecord($parameters['component'] ?: 'test.core.log', $parameters['level'] ?: \TYPO3\CMS\Core\Log\LogLevel::DEBUG, $parameters['message'] ?: 'test message', $parameters['data'] ?: array());
		return $record;
	}

	/**
	 * @test
	 */
	public function constructorSetsCorrectComponent() {
		$component = 'test.core.log';
		$record = $this->getRecord(array('component' => $component));
		$this->assertEquals($component, $record->getComponent());
	}

	/**
	 * @test
	 */
	public function constructorSetsCorrectLogLevel() {
		$logLevel = \TYPO3\CMS\Core\Log\LogLevel::CRITICAL;
		$record = $this->getRecord(array('level' => $logLevel));
		$this->assertEquals($logLevel, $record->getLevel());
	}

	/**
	 * @test
	 */
	public function constructorSetsCorrectMessage() {
		$logMessage = 'test message';
		$record = $this->getRecord(array('message' => $logMessage));
		$this->assertEquals($logMessage, $record->getMessage());
	}

	/**
	 * @test
	 */
	public function constructorSetsCorrectData() {
		$dataArray = array(
			'foo' => 'bar'
		);
		$record = $this->getRecord(array('data' => $dataArray));
		$this->assertEquals($dataArray, $record->getData());
	}

	/**
	 * @test
	 */
	public function setComponentSetsComponent() {
		$record = $this->getRecord();
		$component = 'testcomponent';
		$this->assertEquals($component, $record->setComponent($component)->getComponent());
	}

	/**
	 * @test
	 */
	public function setLevelSetsLevel() {
		$record = $this->getRecord();
		$level = \TYPO3\CMS\Core\Log\LogLevel::EMERGENCY;
		$this->assertEquals($level, $record->setLevel($level)->getLevel());
	}

	/**
	 * @test
	 * @expectedException Psr\Log\InvalidArgumentException
	 */
	public function setLevelValidatesLevel() {
		$record = $this->getRecord();
		$record->setLevel(100);
	}

	/**
	 * @test
	 */
	public function setMessageSetsMessage() {
		$record = $this->getRecord();
		$message = 'testmessage';
		$this->assertEquals($message, $record->setMessage($message)->getMessage());
	}

	/**
	 * @test
	 */
	public function setCreatedSetsCreated() {
		$record = $this->getRecord();
		$created = 123.45;
		$this->assertEquals($created, $record->setCreated($created)->getCreated());
	}

	/**
	 * @test
	 */
	public function setRequestIdSetsRequestId() {
		$record = $this->getRecord();
		$requestId = 'testrequestid';
		$this->assertEquals($requestId, $record->setRequestId($requestId)->getRequestId());
	}

	/**
	 * @test
	 */
	public function toArrayReturnsCorrectValues() {
		$component = 'test.core.log';
		$level = \TYPO3\CMS\Core\Log\LogLevel::DEBUG;
		$message = 'test message';
		$data = array('foo' => 'bar');
		/** @var $record \TYPO3\CMS\Core\Log\LogRecord */
		$record = new \TYPO3\CMS\Core\Log\LogRecord($component, $level, $message, $data);
		$recordArray = $record->toArray();
		$this->assertEquals($component, $recordArray['component']);
		$this->assertEquals($level, $recordArray['level']);
		$this->assertEquals($message, $recordArray['message']);
		$this->assertEquals($data, $recordArray['data']);
	}

	/**
	 * @test
	 */
	public function toStringIncludesDataAsJson() {
		$dataArray = array('foo' => 'bar');
		$record = $this->getRecord(array('data' => $dataArray));
		$this->assertContains(json_encode($dataArray), (string) $record);
	}

	/**
	 * @test
	 */
	public function toStringIncludesExceptionDataAsJson() {
		$dataArray = array('exception' => new \Exception('foo'));
		$record = $this->getRecord(array('data' => $dataArray));
		$this->assertContains('\'Exception\' with message \'foo\'', (string)$record);
	}

}
