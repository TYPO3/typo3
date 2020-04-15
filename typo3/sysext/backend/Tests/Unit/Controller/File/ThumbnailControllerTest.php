<?php

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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller\File;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Controller\File\ThumbnailController;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for \TYPO3\CMS\Backend\Controller\File\ThumbnailController
 */
class ThumbnailControllerTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Backend\Controller\File\ThumbnailController|MockObject
     */
    protected $subject;

    /**
     * @var array
     */
    protected static $parameters = [
        'fileId' => 123,
        'configuration' => [
            'width' => 64,
            'height' => 64,
        ],
    ];

    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
            = '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6';
        $this->subject = $this->createPartialMock(
            ThumbnailController::class,
            ['generateThumbnail']
        );
    }

    /**
     * @param string $hmac
     *
     * @test
     * @dataProvider exceptionIsThrownOnInvalidHMACDataProvider
     */
    public function exceptionIsThrownOnInvalidHMAC(string $hmac = null)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1534484203);

        $queryParameters = [
            'parameters' => json_encode(static::$parameters),
            'hmac' => $hmac,
        ];

        $request = (new ServerRequest())
            ->withQueryParams($queryParameters);
        $this->subject->render($request);
    }

    /**
     * @return array
     */
    public function exceptionIsThrownOnInvalidHMACDataProvider(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'invalid' => ['invalid'],
        ];
    }

    /**
     * @param array|null $parameters
     *
     * @test
     * @dataProvider generateThumbnailIsInvokedDataProvider
     */
    public function generateThumbnailIsInvoked(array $parameters = null)
    {
        $this->subject->expects(self::once())
            ->method('generateThumbnail')
            ->willReturn(new Response());

        $queryParameters = [
            'parameters' => json_encode($parameters),
            'hmac' => GeneralUtility::hmac(
                json_encode($parameters),
                ThumbnailController::class
            ),
        ];

        $request = (new ServerRequest())
            ->withQueryParams($queryParameters);
        self::assertInstanceOf(
            Response::class,
            $this->subject->render($request)
        );
    }

    /**
     * @return array
     */
    public function generateThumbnailIsInvokedDataProvider(): array
    {
        return [
            'null' => [null],
            'empty array' => [[]],
            'parameters' => [static::$parameters],
        ];
    }
}
