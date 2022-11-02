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

use TYPO3\CMS\Backend\Controller\FormInlineAjaxController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FormInlineAjaxControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function createActionThrowsExceptionIfContextIsEmpty(): void
    {
        $request = (new ServerRequest())->withQueryParams(
            [
                'ajax' => [
                    'context' => '',
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751361);
        (new FormInlineAjaxController())->createAction($request);
    }

    /**
     * @test
     */
    public function createActionThrowsExceptionIfContextConfigSectionIsEmpty(): void
    {
        $request = (new ServerRequest())->withQueryParams(
            [
                'ajax' => [
                    'context' => json_encode([ 'config' => '' ]),
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751362);
        (new FormInlineAjaxController())->createAction($request);
    }

    /**
     * @test
     */
    public function createActionThrowsExceptionIfContextConfigSectionDoesNotValidate(): void
    {
        $request = (new ServerRequest())->withQueryParams(
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
        (new FormInlineAjaxController())->createAction($request);
    }

    /**
     * @test
     */
    public function detailsActionThrowsExceptionIfContextIsEmpty(): void
    {
        $request = (new ServerRequest())->withQueryParams(
            [
                'ajax' => [
                    'context' => '',
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751361);
        (new FormInlineAjaxController())->detailsAction($request);
    }

    /**
     * @test
     */
    public function detailsActionThrowsExceptionIfContextConfigSectionIsEmpty(): void
    {
        $request = (new ServerRequest())->withQueryParams(
            [
                'ajax' => [
                    'context' => json_encode([ 'config' => '' ]),
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751362);
        (new FormInlineAjaxController())->detailsAction($request);
    }

    /**
     * @test
     */
    public function detailsActionThrowsExceptionIfContextConfigSectionDoesNotValidate(): void
    {
        $request = (new ServerRequest())->withQueryParams(
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
        (new FormInlineAjaxController())->detailsAction($request);
    }

    /**
     * @test
     */
    public function synchronizeLocalizeActionThrowsExceptionIfContextIsEmpty(): void
    {
        $request = (new ServerRequest())->withQueryParams(
            [
                'ajax' => [
                    'context' => '',
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751361);
        (new FormInlineAjaxController())->synchronizeLocalizeAction($request);
    }

    /**
     * @test
     */
    public function synchronizeLocalizeActionThrowsExceptionIfContextConfigSectionIsEmpty(): void
    {
        $request = (new ServerRequest())->withQueryParams(
            [
                'ajax' => [
                    'context' => json_encode([ 'config' => '' ]),
                ],
            ]
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489751362);
        (new FormInlineAjaxController())->synchronizeLocalizeAction($request);
    }

    /**
     * @test
     */
    public function synchronizeLocalizeActionThrowsExceptionIfContextConfigSectionDoesNotValidate(): void
    {
        $request = (new ServerRequest())->withQueryParams(
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
        (new FormInlineAjaxController())->synchronizeLocalizeAction($request);
    }

    /**
     * Fallback for IRRE items without inline view attribute
     * @see https://forge.typo3.org/issues/76561
     *
     * @test
     */
    public function getInlineExpandCollapseStateArraySwitchesToFallbackIfTheBackendUserDoesNotHaveAnUCInlineViewProperty(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);

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
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->uc = ['inlineView' => json_encode(['foo' => 'bar'])];

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
