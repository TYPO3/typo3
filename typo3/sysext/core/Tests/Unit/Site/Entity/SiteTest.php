<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\Unit\Site\Entity;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Error\PageErrorHandler\FluidPageErrorHandler;
use TYPO3\CMS\Core\Error\PageErrorHandler\InvalidPageErrorHandlerException;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageContentErrorHandler;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SiteTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getErrorHandlerReturnsConfiguredErrorHandler()
    {
        $subject = new Site('aint-misbehaving', 13, [
            'languages' => [],
            'errorHandling' => [
                [
                    'errorCode' => 123,
                    'errorHandler' => 'Fluid',
                ],
                [
                    'errorCode' => 124,
                    'errorContentSource' => 123,
                    'errorHandler' => 'Page'
                ],
                [
                    'errorCode' => 125,
                    'errorHandler' => 'PHP',
                    'errorContentSource' => 123,
                    'errorPhpClassFQCN' => PageContentErrorHandler::class
                ]
            ]
        ]);

        $fluidProphecy = $this->prophesize(FluidPageErrorHandler::class);
        GeneralUtility::addInstance(FluidPageErrorHandler::class, $fluidProphecy->reveal());

        $this->assertEquals(true, $subject->getErrorHandler(123) instanceof PageErrorHandlerInterface);
        $this->assertEquals(true, $subject->getErrorHandler(124) instanceof PageErrorHandlerInterface);
        $this->assertEquals(true, $subject->getErrorHandler(125) instanceof PageErrorHandlerInterface);
    }

    /**
     * @test
     */
    public function getErrorHandlerThrowsExceptionOnInvalidErrorHandler()
    {
        $this->expectException(InvalidPageErrorHandlerException::class);
        $this->expectExceptionCode(1527432330);
        $this->expectExceptionMessage('The configured error handler "' . BackendUtility::class . '" for status code 404 must implement the PageErrorHandlerInterface.');
        $subject = new Site('aint-misbehaving', 13, [
            'languages' => [],
            'errorHandling' => [
                [
                    'errorCode' => 404,
                    'errorHandler' => 'PHP',
                    'errorPhpClassFQCN' => BackendUtility::class
                ],
            ]
        ]);
        $subject->getErrorHandler(404);
    }

    /**
     * @test
     */
    public function getErrorHandlerThrowsExceptionWhenNoErrorHandlerIsConfigured()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1522495914);
        $this->expectExceptionMessage('No error handler given for the status code "404".');
        $subject = new Site('aint-misbehaving', 13, ['languages' => []]);
        $subject->getErrorHandler(404);
    }

    /**
     * @test
     */
    public function getErrorHandlerThrowsExceptionWhenNoErrorHandlerForStatusCodeIsConfigured()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1522495914);
        $this->expectExceptionMessage('No error handler given for the status code "404".');
        $subject = new Site('aint-misbehaving', 13, [
            'languages' => [],
            'errorHandling' => [
                [
                    'errorCode' => 403,
                    'errorHandler' => 'Does it really matter?'
                ],
            ]
        ]);
        $subject->getErrorHandler(404);
    }
}
