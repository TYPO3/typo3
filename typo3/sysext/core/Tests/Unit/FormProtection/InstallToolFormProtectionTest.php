<?php
namespace TYPO3\CMS\Core\Tests\Unit\FormProtection;

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
 * Testcase
 */
class InstallToolFormProtectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class,
            ['dummy']
        );
    }

    //////////////////////////////////////////////////////////
    // Tests concerning the reading and saving of the tokens
    //////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function tokenFromSessionDataIsAvailableForValidateToken()
    {
        $sessionToken = '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd';
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = '42';

        $tokenId = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac($formName . $action . $formInstanceName . $sessionToken);

        $_SESSION['installToolFormToken'] = $sessionToken;

        $this->subject->_call('retrieveSessionToken');

        $this->assertTrue(
            $this->subject->validateToken($tokenId, $formName, $action, $formInstanceName)
        );
    }

    /**
     * @test
     */
    public function persistSessionTokenWritesTokensToSession()
    {
        $_SESSION['installToolFormToken'] = 'foo';

        $this->subject->_set('sessionToken', '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd');

        $this->subject->persistSessionToken();

        $this->assertEquals(
            '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd',
            $_SESSION['installToolFormToken']
        );
    }
}
