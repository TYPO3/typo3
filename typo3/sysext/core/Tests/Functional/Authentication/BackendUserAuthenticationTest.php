<?php
declare(strict_types = 1);

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
        $this->assertNotNull($result);
    }

    /**
     * @test
     */
    public function returnWebmountsFilterOutInaccessiblePages(): void
    {
        $result = $this->subject->returnWebmounts();

        self::assertNotContains('3', $result, 'Deleted page is not filtered out');
        self::assertNotContains('4', $result, 'Page user has no permission to read is not filtered out');
        self::assertNotContains('5', $result, 'Not existing page is not filtered out');
        self::assertContains('40', $result, 'Accessible db mount page, child of a not accessible page is not shown');
        self::assertEquals(['1', '40'], $result);
    }
}
