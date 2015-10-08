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

use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class LoginControllerTest
 */
class LoginControllerTest extends UnitTestCase
{
    /**
     * @var LoginController|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $loginControllerMock;

    /**
     * @throws \InvalidArgumentException
     */
    protected function setUp()
    {
        $this->loginControllerMock = $this->getAccessibleMock(LoginController::class, ['dummy'], [], '', false);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionCode 1433417281
     */
    public function validateAndSortLoginProvidersDetectsMissingProviderConfiguration()
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders']);
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionCode 1433417281
     */
    public function validateAndSortLoginProvidersDetectsNonArrayProviderConfiguration()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = 'foo';
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionCode 1433417281
     */
    public function validateAndSortLoginProvidersDetectsIfNoProviderIsRegistered()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [];
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionCode 1433416043
     */
    public function validateAndSortLoginProvidersDetectsMissingConfigurationForProvider()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => []
        ];
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionCode 1433416043
     */
    public function validateAndSortLoginProvidersDetectsWrongProvider()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [
                'provider' => \stdClass::class
            ]
        ];
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionCode 1433416044
     */
    public function validateAndSortLoginProvidersDetectsMissingLabel()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [
                'provider' => UsernamePasswordLoginProvider::class,
                'sorting' => 30,
                'icon-class' => 'foo'
            ]
        ];
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionCode 1433416045
     */
    public function validateAndSortLoginProvidersDetectsMissingIconClass()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [
                'provider' => UsernamePasswordLoginProvider::class,
                'sorting' => 30,
                'label' => 'foo'
            ]
        ];
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionCode 1433416046
     */
    public function validateAndSortLoginProvidersDetectsMissingSorting()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [
                'provider' => UsernamePasswordLoginProvider::class,
                'label' => 'foo',
                'icon-class' => 'foo'
            ]
        ];
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }
}
