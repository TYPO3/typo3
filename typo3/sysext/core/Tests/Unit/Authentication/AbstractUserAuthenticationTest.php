<?php
namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

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
 * Testcase for class \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication
 *
 */
class AbstractUserAuthenticationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getAuthInfoArrayReturnsEmptyPidListIfNoCheckPidValueIsGiven()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['cleanIntList']);
        $GLOBALS['TYPO3_DB']->expects($this->never())->method('cleanIntList');

        /** @var $mock \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication */
        $mock = $this->getMock(\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class, ['dummy']);
        $mock->checkPid = true;
        $mock->checkPid_value = null;
        $result = $mock->getAuthInfoArray();
        $this->assertEquals('', $result['db_user']['checkPidList']);
    }
}
