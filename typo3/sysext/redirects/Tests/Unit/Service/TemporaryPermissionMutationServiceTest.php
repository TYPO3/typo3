<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Redirects\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Redirects\Service\TemporaryPermissionMutationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TemporaryPermissionMutationServiceTest extends UnitTestCase
{
    private TemporaryPermissionMutationService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TemporaryPermissionMutationService();
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
        parent::tearDown();
    }

    #[Test]
    public function addTableSelectAddsSysRedirectPermission(): void
    {
        $GLOBALS['BE_USER']->groupData['tables_select'] = 'pages,tt_content';
        $result = $this->subject->addTableSelect();
        self::assertTrue($result);
        self::assertSame('pages,tt_content,sys_redirect', $GLOBALS['BE_USER']->groupData['tables_select']);
    }

    #[Test]
    public function addTableSelectReturnsFalseIfAlreadyPresent(): void
    {
        $GLOBALS['BE_USER']->groupData['tables_select'] = 'pages,sys_redirect,tt_content';
        $result = $this->subject->addTableSelect();
        self::assertFalse($result);
        self::assertSame('pages,sys_redirect,tt_content', $GLOBALS['BE_USER']->groupData['tables_select']);
    }

    #[Test]
    public function addTableModifyAddsSysRedirectPermission(): void
    {
        $GLOBALS['BE_USER']->groupData['tables_modify'] = 'pages,tt_content';
        $result = $this->subject->addTableModify();
        self::assertTrue($result);
        self::assertSame('pages,tt_content,sys_redirect', $GLOBALS['BE_USER']->groupData['tables_modify']);
    }

    #[Test]
    public function addTableModifyReturnsFalseIfAlreadyPresent(): void
    {
        $GLOBALS['BE_USER']->groupData['tables_modify'] = 'pages,sys_redirect,tt_content';
        $result = $this->subject->addTableModify();
        self::assertFalse($result);
        self::assertSame('pages,sys_redirect,tt_content', $GLOBALS['BE_USER']->groupData['tables_modify']);
    }

    #[Test]
    public function removeTableSelectPreservesExistingPermissions(): void
    {
        $GLOBALS['BE_USER']->groupData['tables_select'] = 'pages,sys_redirect,tt_content';
        $this->subject->removeTableSelect();
        self::assertSame('pages,tt_content', $GLOBALS['BE_USER']->groupData['tables_select']);
    }

    #[Test]
    public function removeTableModifyPreservesExistingPermissions(): void
    {
        $GLOBALS['BE_USER']->groupData['tables_modify'] = 'pages,sys_redirect,tt_content';
        $this->subject->removeTableModify();
        self::assertSame('pages,tt_content', $GLOBALS['BE_USER']->groupData['tables_modify']);
    }

    #[Test]
    public function removeTableSelectDoesNothingIfNotPresent(): void
    {
        $GLOBALS['BE_USER']->groupData['tables_select'] = 'pages,tt_content';
        $this->subject->removeTableSelect();
        self::assertSame('pages,tt_content', $GLOBALS['BE_USER']->groupData['tables_select']);
    }

    #[Test]
    public function removeTableModifyDoesNothingIfNotPresent(): void
    {
        $GLOBALS['BE_USER']->groupData['tables_modify'] = 'pages,tt_content';
        $this->subject->removeTableModify();
        self::assertSame('pages,tt_content', $GLOBALS['BE_USER']->groupData['tables_modify']);
    }

    #[Test]
    public function addAndRemoveTableSelectRoundTripsCorrectly(): void
    {
        $GLOBALS['BE_USER']->groupData['tables_select'] = 'pages,tt_content';
        $wasAdded = $this->subject->addTableSelect();
        self::assertTrue($wasAdded);
        self::assertSame('pages,tt_content,sys_redirect', $GLOBALS['BE_USER']->groupData['tables_select']);

        $this->subject->removeTableSelect();
        self::assertSame('pages,tt_content', $GLOBALS['BE_USER']->groupData['tables_select']);
    }

    #[Test]
    public function addAndRemoveTableModifyRoundTripsCorrectly(): void
    {
        $GLOBALS['BE_USER']->groupData['tables_modify'] = 'pages,tt_content';
        $wasAdded = $this->subject->addTableModify();
        self::assertTrue($wasAdded);
        self::assertSame('pages,tt_content,sys_redirect', $GLOBALS['BE_USER']->groupData['tables_modify']);

        $this->subject->removeTableModify();
        self::assertSame('pages,tt_content', $GLOBALS['BE_USER']->groupData['tables_modify']);
    }
}
