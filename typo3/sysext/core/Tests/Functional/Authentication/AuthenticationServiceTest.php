<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Functional\Authentication;

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
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Testcase for class \TYPO3\CMS\Core\Authentication\AuthenticationService
 */
class AuthenticationServiceTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @var AuthenticationService
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new AuthenticationService();
        $this->subject->setLogger(new NullLogger());
        parent::setUp();
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/be_users.xml');
    }

    /**
     * @test
     */
    public function getUserReturnsOnlyNotDeletedRecords()
    {
        $this->subject->pObj = new BackendUserAuthentication();
        $this->subject->login = [
            'status' => 'login',
            'uname' => 'test1',
            'uident' => 'password',
            'uident_text' => 'password',
        ];
        $this->subject->db_user = [
            'table' => 'be_users',
            'check_pid_clause' => '',
            'enable_clause' => '',
            'username_column' => 'username',
        ];
        $expected = [
            'username' => 'test1',
            'deleted' => 0
        ];
        $result = $this->subject->getUser();
        $this->assertArraySubset($expected, $result);
    }
}
