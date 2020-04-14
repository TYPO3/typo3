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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\FormInlineAjaxController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FormInlineAjaxControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function createActionThrowsExceptionIfContextIsEmpty(): void
    {
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
        (new FormInlineAjaxController())->createAction($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function createActionThrowsExceptionIfContextConfigSectionIsEmpty(): void
    {
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
        (new FormInlineAjaxController())->createAction($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function createActionThrowsExceptionIfContextConfigSectionDoesNotValidate(): void
    {
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
        (new FormInlineAjaxController())->createAction($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function detailsActionThrowsExceptionIfContextIsEmpty(): void
    {
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
        (new FormInlineAjaxController())->detailsAction($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function detailsActionThrowsExceptionIfContextConfigSectionIsEmpty(): void
    {
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
        (new FormInlineAjaxController())->detailsAction($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function detailsActionThrowsExceptionIfContextConfigSectionDoesNotValidate(): void
    {
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
        (new FormInlineAjaxController())->detailsAction($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function synchronizeLocalizeActionThrowsExceptionIfContextIsEmpty(): void
    {
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
        (new FormInlineAjaxController())->synchronizeLocalizeAction($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function synchronizeLocalizeActionThrowsExceptionIfContextConfigSectionIsEmpty(): void
    {
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
        (new FormInlineAjaxController())->synchronizeLocalizeAction($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function synchronizeLocalizeActionThrowsExceptionIfContextConfigSectionDoesNotValidate(): void
    {
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
        (new FormInlineAjaxController())->synchronizeLocalizeAction($requestProphecy->reveal());
    }

    /**
     * Fallback for IRRE items without inline view attribute
     * @see https://forge.typo3.org/issues/76561
     *
     * @test
     */
    public function getInlineExpandCollapseStateArraySwitchesToFallbackIfTheBackendUserDoesNotHaveAnUCInlineViewProperty(): void
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

        self::assertEmpty($result);
    }

    /**
     * Unserialize uc inline view string for IRRE item
     * @see https://forge.typo3.org/issues/76561
     *
     * @test
     */
    public function getInlineExpandCollapseStateArrayWillUnserializeUCInlineViewPropertyAsAnArrayWithData(): void
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $backendUserProphecy->uc = ['inlineView' => json_encode(['foo' => 'bar'])];
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

        self::assertNotEmpty($result);
    }
}
