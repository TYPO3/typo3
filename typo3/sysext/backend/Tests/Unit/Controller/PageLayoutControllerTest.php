<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

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

use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * Class PageLayoutControllerTest
 */
class PageLayoutControllerTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider pageIsNotLockedForEditorsReturnsCorrectValueDataProvider
     * @param bool $isAdmin
     * @param int $permissions
     * @param bool $editLock
     * @param bool $expected
     */
    public function pageIsNotLockedForEditorsReturnsCorrectValue($isAdmin, $permissions, $editLock, $expected)
    {
        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $beUserMock */
        $beUserMock = $this->getMock(BackendUserAuthentication::class, ['isAdmin']);
        $beUserMock->method('isAdmin')->will($this->returnValue($isAdmin));

        /** @var PageLayoutController|\PHPUnit_Framework_MockObject_MockObject $pageController */
        $pageController = $this->getMock(PageLayoutController::class, ['getBackendUser']);
        $pageController->method('getBackendUser')->will($this->returnValue($beUserMock));

        $pageController->CALC_PERMS = $permissions;
        $pageController->pageinfo = ['editlock' => $editLock];

        $this->assertTrue($pageController->pageIsNotLockedForEditors() === $expected);
    }

    /**
     * @return array
     */
    public function pageIsNotLockedForEditorsReturnsCorrectValueDataProvider()
    {
        return [
            'user is admin' => [ true, 0, false, true],
            'user has permission' => [ false, Permission::PAGE_EDIT, false, true],
            'page has permission, but editlock set' => [ false, Permission::PAGE_EDIT, true, false],
            'user does not have permission' => [ false, 0, false, false],
        ];
    }
}
