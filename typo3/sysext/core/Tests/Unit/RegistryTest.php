<?php
namespace TYPO3\CMS\Core\Tests\Unit;

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
 * Testcase for TYPO3\CMS\Core\Registry
 */
class RegistryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Registry
     */
    protected $registry;

    /**
     * Sets up this testcase
     */
    protected function setUp()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, []);
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('fullQuoteStr')->will($this->onConsecutiveCalls('\'tx_phpunit\'', '\'someKey\'', '\'tx_phpunit\'', '\'someKey\''));
        $this->registry = new \TYPO3\CMS\Core\Registry();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getThrowsExceptionForInvalidNamespacesUsingNoNamespace()
    {
        $this->registry->get('', 'someKey');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getThrowsExceptionForInvalidNamespacesUsingTooShortNamespace()
    {
        $this->registry->get('t', 'someKey');
    }

    /**
     * @test
     */
    public function getRetrievesTheCorrectEntry()
    {
        $testKey = 'TYPO3\\CMS\\Core\\Registry_testcase.testData.getRetrievesTheCorrectEntry';
        $testValue = 'getRetrievesTheCorrectEntry';
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_SELECTgetRows')->with('*', 'sys_registry', 'entry_namespace = \'tx_phpunit\'')->will($this->returnValue([
            ['entry_key' => $testKey, 'entry_value' => serialize($testValue)]
        ]));
        $this->assertEquals($this->registry->get('tx_phpunit', $testKey), $testValue, 'The actual data did not match the expected data.');
    }

    /**
     * @test
     */
    public function getLazyLoadsEntriesOfOneNamespace()
    {
        $testKey1 = 'TYPO3\\CMS\\Core\\Registry_testcase.testData.getLazyLoadsEntriesOfOneNamespace1';
        $testValue1 = 'getLazyLoadsEntriesOfOneNamespace1';
        $testKey2 = 'TYPO3\\CMS\\Core\\Registry_testcase.testData.getLazyLoadsEntriesOfOneNamespace2';
        $testValue2 = 'getLazyLoadsEntriesOfOneNamespace2';
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_SELECTgetRows')->with('*', 'sys_registry', 'entry_namespace = \'tx_phpunit\'')->will($this->returnValue([
            ['entry_key' => $testKey1, 'entry_value' => serialize($testValue1)],
            ['entry_key' => $testKey2, 'entry_value' => serialize($testValue2)]
        ]));
        $this->assertEquals($this->registry->get('tx_phpunit', $testKey1), $testValue1, 'The actual data did not match the expected data.');
        $this->assertEquals($this->registry->get('tx_phpunit', $testKey2), $testValue2, 'The actual data did not match the expected data.');
    }

    /**
     * @test
     */
    public function getReturnsTheDefaultValueIfTheRequestedKeyWasNotFound()
    {
        $defaultValue = 'getReturnsTheDefaultValueIfTheRequestedKeyWasNotFound';
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_SELECTgetRows')->with('*', 'sys_registry', 'entry_namespace = \'tx_phpunit\'')->will($this->returnValue([
            ['entry_key' => 'foo', 'entry_value' => 'bar']
        ]));
        $this->assertEquals($defaultValue, $this->registry->get('tx_phpunit', 'someNonExistingKey', $defaultValue), 'A value other than the default value was returned.');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setThrowsAnExceptionOnEmptyNamespace()
    {
        $this->registry->set('', 'someKey', 'someValue');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setThrowsAnExceptionOnWrongNamespace()
    {
        $this->registry->set('t', 'someKey', 'someValue');
    }

    /**
     * @test
     */
    public function setAllowsValidNamespaces()
    {
        $registry = $this->getMock(\TYPO3\CMS\Core\Registry::class, ['loadEntriesByNamespace']);
        $registry->set('tx_thisIsValid', 'someKey', 'someValue');
        $registry->set('thisIsValid', 'someKey', 'someValue');
        $registry->set('user_soIsThis', 'someKey', 'someValue');
        $registry->set('core', 'someKey', 'someValue');
    }

    /**
     * @test
     */
    public function setReallySavesTheGivenValueToTheDatabase()
    {
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_INSERTquery')->with('sys_registry', [
            'entry_namespace' => 'tx_phpunit',
            'entry_key' => 'someKey',
            'entry_value' => serialize('someValue')
        ]);
        $registry = $this->getMock(\TYPO3\CMS\Core\Registry::class, ['loadEntriesByNamespace']);
        $registry->set('tx_phpunit', 'someKey', 'someValue');
    }

    /**
     * @test
     */
    public function setUpdatesExistingKeys()
    {
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_SELECTquery')->with('uid', 'sys_registry', 'entry_namespace = \'tx_phpunit\' AND entry_key = \'someKey\'')->will($this->returnValue('DBResource'));
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('sql_num_rows')->with('DBResource')->will($this->returnValue(1));
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_UPDATEquery')->with('sys_registry', 'entry_namespace = \'tx_phpunit\' AND entry_key = \'someKey\'', [
            'entry_value' => serialize('someValue')
        ]);
        $GLOBALS['TYPO3_DB']->expects($this->never())->method('exec_INSERTquery');
        $registry = $this->getMock(\TYPO3\CMS\Core\Registry::class, ['loadEntriesByNamespace']);
        $registry->set('tx_phpunit', 'someKey', 'someValue');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function removeThrowsAnExceptionOnWrongNamespace()
    {
        $this->registry->remove('t', 'someKey');
    }

    /**
     * @test
     */
    public function removeReallyRemovesTheEntryFromTheDatabase()
    {
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_DELETEquery')->with('sys_registry', 'entry_namespace = \'tx_phpunit\' AND entry_key = \'someKey\'');
        $this->registry->remove('tx_phpunit', 'someKey');
    }

    /**
     * @test
     */
    public function removeUnsetsValueFromTheInternalEntriesCache()
    {
        $registry = $this->getMock(\TYPO3\CMS\Core\Registry::class, ['loadEntriesByNamespace']);
        $registry->set('tx_phpunit', 'someKey', 'someValue');
        $registry->set('tx_phpunit', 'someOtherKey', 'someOtherValue');
        $registry->set('tx_otherNamespace', 'someKey', 'someValueInOtherNamespace');
        $registry->remove('tx_phpunit', 'someKey');
        $this->assertEquals('defaultValue', $registry->get('tx_phpunit', 'someKey', 'defaultValue'), 'A value other than the default value was returned, thus the entry was still present.');
        $this->assertEquals('someOtherValue', $registry->get('tx_phpunit', 'someOtherKey', 'defaultValue'), 'A value other than the stored value was returned, thus the entry was removed.');
        $this->assertEquals('someValueInOtherNamespace', $registry->get('tx_otherNamespace', 'someKey', 'defaultValue'), 'A value other than the stored value was returned, thus the entry was removed.');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function removeAllByNamespaceThrowsAnExceptionOnWrongNamespace()
    {
        $this->registry->removeAllByNamespace('');
    }

    /**
     * @test
     */
    public function removeAllByNamespaceReallyRemovesAllEntriesOfTheSpecifiedNamespaceFromTheDatabase()
    {
        $GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_DELETEquery')->with('sys_registry', 'entry_namespace = \'tx_phpunit\'');
        $this->registry->removeAllByNamespace('tx_phpunit');
    }

    /**
     * @test
     */
    public function removeAllByNamespaceUnsetsValuesOfTheSpecifiedNamespaceFromTheInternalEntriesCache()
    {
        $registry = $this->getMock(\TYPO3\CMS\Core\Registry::class, ['loadEntriesByNamespace']);
        $registry->set('tx_phpunit', 'someKey', 'someValue');
        $registry->set('tx_phpunit', 'someOtherKey', 'someOtherValue');
        $registry->set('tx_otherNamespace', 'someKey', 'someValueInOtherNamespace');
        $registry->removeAllByNamespace('tx_phpunit');
        $this->assertEquals('defaultValue', $registry->get('tx_phpunit', 'someKey', 'defaultValue'), 'A value other than the default value was returned, thus the entry was still present.');
        $this->assertEquals('defaultValue', $registry->get('tx_phpunit', 'someOtherKey', 'defaultValue'), 'A value other than the default value was returned, thus the entry was still present.');
        $this->assertEquals('someValueInOtherNamespace', $registry->get('tx_otherNamespace', 'someKey', 'defaultValue'), 'A value other than the stored value was returned, thus the entry was removed.');
    }
}
