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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\FormInlineAjaxController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Test case
 */
class FormInlineAjaxControllerTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function createActionThrowsExceptionIfContextIsEmpty()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()->shouldBeCalled()->willReturn(
            [
                'ajax' => [
                    'context' => '',
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751361);
        (new FormInlineAjaxController())->createAction($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function createActionThrowsExceptionIfContextConfigSectionIsEmpty()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()->shouldBeCalled()->willReturn(
            [
                'ajax' => [
                    'context' => json_encode([ 'config' => '' ]),
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751362);
        (new FormInlineAjaxController())->createAction($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function createActionThrowsExceptionIfContextConfigSectionDoesNotValidate()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()->shouldBeCalled()->willReturn(
            [
                'ajax' => [
                    'context' => json_encode(
                        [
                            'config' => json_encode([
                                'type' => 'inline',
                            ]),
                            'hmac' => 'anInvalidHash',
                        ]
                    ),
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751363);
        (new FormInlineAjaxController())->createAction($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function detailsActionThrowsExceptionIfContextIsEmpty()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()->shouldBeCalled()->willReturn(
            [
                'ajax' => [
                    'context' => '',
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751361);
        (new FormInlineAjaxController())->detailsAction($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function detailsActionThrowsExceptionIfContextConfigSectionIsEmpty()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()->shouldBeCalled()->willReturn(
            [
                'ajax' => [
                    'context' => json_encode([ 'config' => '' ]),
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751362);
        (new FormInlineAjaxController())->detailsAction($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function detailsActionThrowsExceptionIfContextConfigSectionDoesNotValidate()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()->shouldBeCalled()->willReturn(
            [
                'ajax' => [
                    'context' => json_encode(
                        [
                            'config' => json_encode([
                                'type' => 'inline',
                            ]),
                            'hmac' => 'anInvalidHash',
                        ]
                    ),
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751363);
        (new FormInlineAjaxController())->detailsAction($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function synchronizeLocalizeActionThrowsExceptionIfContextIsEmpty()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()->shouldBeCalled()->willReturn(
            [
                'ajax' => [
                    'context' => '',
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751361);
        (new FormInlineAjaxController())->synchronizeLocalizeAction($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function synchronizeLocalizeActionThrowsExceptionIfContextConfigSectionIsEmpty()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()->shouldBeCalled()->willReturn(
            [
                'ajax' => [
                    'context' => json_encode([ 'config' => '' ]),
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751362);
        (new FormInlineAjaxController())->synchronizeLocalizeAction($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function synchronizeLocalizeActionThrowsExceptionIfContextConfigSectionDoesNotValidate()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()->shouldBeCalled()->willReturn(
            [
                'ajax' => [
                    'context' => json_encode(
                        [
                            'config' => json_encode([
                                'type' => 'inline',
                            ]),
                            'hmac' => 'anInvalidHash',
                        ]
                    ),
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751363);
        (new FormInlineAjaxController())->synchronizeLocalizeAction($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * Fallback for IRRE items without inline view attribute
     * @issue https://forge.typo3.org/issues/76561
     *
     * @test
     */
    public function getInlineExpandCollapseStateArraySwitchesToFallbackIfTheBackendUserDoesNotHaveAnUCInlineViewProperty()
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $backendUserProphecy->uc = [];
        $backendUser = $backendUserProphecy->reveal();

        $mockObject = $this->getAccessibleMock(
            FormInlineAjaxController::class,
            ['getBackendUserAuthentication'],
            [],
            '',
            false
        );
        $mockObject->method('getBackendUserAuthentication')->willReturn($backendUser);
        $result = $mockObject->_call('getInlineExpandCollapseStateArray');

        $this->assertEmpty($result);
    }

    /**
     * Unserialize uc inline view string for IRRE item
     * @issue https://forge.typo3.org/issues/76561
     *
     * @test
     */
    public function getInlineExpandCollapseStateArrayWillUnserializeUCInlineViewPropertyAsAnArrayWithData()
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $backendUserProphecy->uc = ['inlineView' => serialize(['foo' => 'bar'])];
        $backendUser = $backendUserProphecy->reveal();

        $mockObject = $this->getAccessibleMock(
            FormInlineAjaxController::class,
            ['getBackendUserAuthentication'],
            [],
            '',
            false
        );
        $mockObject->method('getBackendUserAuthentication')->willReturn($backendUser);
        $result = $mockObject->_call('getInlineExpandCollapseStateArray');

        $this->assertNotEmpty($result);
    }
}
