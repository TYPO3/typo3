<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Http;

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

use PHPUnit_Framework_MockObject_MockObject;
use TYPO3\CMS\Backend\Http\BackendModuleRequestHandler;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class BackendModuleRequestHandlerTest
 */
class BackendModuleRequestHandlerTest extends UnitTestCase
{
    /**
     * @var BackendModuleRequestHandler|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var \TYPO3\CMS\Core\FormProtection\AbstractFormProtection|PHPUnit_Framework_MockObject_MockObject
     */
    protected $formProtectionMock;

    /**
     * @var \TYPO3\CMS\Core\Http\ServerRequest|PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    protected function setUp()
    {
        $this->requestMock = $this->getAccessibleMock(\TYPO3\CMS\Core\Http\ServerRequest::class, [], [], '', false);
        $this->formProtectionMock = $this->getMockForAbstractClass(BackendFormProtection::class, [], '', false, true, true, ['validateToken']);
        $this->subject = $this->getAccessibleMock(BackendModuleRequestHandler::class, ['boot', 'getFormProtection'], [\TYPO3\CMS\Core\Core\Bootstrap::getInstance()], '', true);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1425236663
     */
    public function moduleIndexIsCalled()
    {
        $GLOBALS['TBE_MODULES'] = [
            '_PATHS' => [
                'module_fixture' => __DIR__ . '/../Fixtures/ModuleFixture/'
            ]
        ];

        $this->requestMock->expects($this->any())->method('getQueryParams')->will($this->returnValue(['M' => 'module_fixture']));
        $this->formProtectionMock->expects($this->once())->method('validateToken')->will($this->returnValue(true));
        $this->subject->expects($this->once())->method('boot');
        $this->subject->expects($this->atLeastOnce())->method('getFormProtection')->will($this->returnValue($this->formProtectionMock));

        $this->subject->handleRequest($this->requestMock);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Exception
     * @expectedExceptionCode 1417988921
     */
    public function throwsExceptionIfTokenIsInvalid()
    {
        $this->formProtectionMock->expects($this->once())->method('validateToken')->will($this->returnValue(false));
        $this->subject->expects($this->once())->method('boot');
        $this->subject->expects($this->atLeastOnce())->method('getFormProtection')->will($this->returnValue($this->formProtectionMock));

        $this->subject->handleRequest($this->requestMock);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1425236663
     */
    public function moduleDispatcherIsCalled()
    {
        $GLOBALS['TBE_MODULES'] = [
            '_PATHS' => [
                'module_fixture' => __DIR__ . '/../Fixtures/ModuleFixture/'
            ]
        ];
        $this->requestMock->expects($this->any())->method('getQueryParams')->will($this->returnValue(['M' => 'module_fixture']));
        $this->formProtectionMock->expects($this->once())->method('validateToken')->will($this->returnValue(true));
        $this->subject->expects($this->once())->method('boot');
        $this->subject->expects($this->atLeastOnce())->method('getFormProtection')->will($this->returnValue($this->formProtectionMock));

        $this->subject->handleRequest($this->requestMock);
    }
}
