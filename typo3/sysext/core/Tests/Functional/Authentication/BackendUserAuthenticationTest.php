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

use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
 */
class BackendUserAuthenticationTest extends FunctionalTestCase
{
    /**
     * The fixture which is used when initializing a backend user
     *
     * @var string
     */
    protected $backendUserFixture = __DIR__ . '/Fixtures/be_users.xml';

    /**
     * @var AuthenticationService
     */
    protected $authenticationService;

    /**
     * @var BackendUserAuthentication
     */
    protected $subject;

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \TYPO3\TestingFramework\Core\Exception
     */
    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts'] = 1;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName'] = 'be_typo_user';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'] = 4;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIPv6'] = 8;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] = 28800;

        $this->subject = new BackendUserAuthentication();
        parent::setUp();
        $this->importDataSet(__DIR__ . '/Fixtures/be_groups.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/pages.xml');
        $this->setUpBackendUserFromFixture(2);
        /** @var $GLOBALS['BE_USER'] BackendUserAuthentication */
        $this->subject = $GLOBALS['BE_USER'];
    }

    /**
     * @test
     */
    public function getTranslatedPageOnWebMountIsInWebMountForNonAdminUser(): void
    {
        $result = $this->subject->isInWebMount(2);
        self::assertNotNull($result);
    }

    /**
     * @test
     */
    public function userTsConfigIsResolvedProperlyWithPrioritization(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] = "custom.generic = installation-wide-configuration\ncustom.property = from configuration";
        $this->subject->user['realName'] = 'Test user';
        $this->subject->user['TSconfig'] = 'custom.property = from user';
        $this->subject->includeGroupArray[] = 13;
        $this->subject->userGroups[13]['TSconfig'] = "custom.property = from group\ncustom.groupProperty = 13";
        $this->subject->fetchGroupData();
        $result = $this->subject->getTSConfig();
        self::assertEquals($this->subject->user['realName'], $result['TCAdefaults.']['sys_note.']['author']);
        self::assertEquals('from user', $result['custom.']['property']);
        self::assertEquals('13', $result['custom.']['groupProperty']);
        self::assertEquals('installation-wide-configuration', $result['custom.']['generic']);
    }
}
