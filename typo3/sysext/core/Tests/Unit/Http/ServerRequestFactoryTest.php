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

namespace TYPO3\CMS\Core\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ServerRequestFactoryTest extends UnitTestCase
{
    #[Test]
    public function serverRequestHasMethodSet(): void
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/');
        self::assertSame('POST', $request->getMethod());
    }

    #[Test]
    public function serverRequestFactoryHasAWritableEmptyBody(): void
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('GET', '/');
        $body = $request->getBody();

        self::assertSame('', $body->__toString());
        self::assertSame(0, $body->getSize());
        self::assertTrue($body->isSeekable());

        $body->write('Foo');
        self::assertSame(3, $body->getSize());
        self::assertSame('Foo', $body->__toString());
    }

    #[Test]
    public function raisesExceptionForInvalidMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717275);
        $factory = new ServerRequestFactory();
        $factory->createServerRequest('BOGUS-BODY', '/');
    }

    #[Test]
    public function uploadedFilesAreNormalizedFromFilesSuperGlobal(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER['REMOTE_ADDR'] = '';
        $_SERVER['SSL_SESSION_ID'] = '';
        $_FILES = [
            'tx_uploadexample_piexample' => [
                'name' => [
                    'newExample' => [
                        'image' => 'o51pb.jpg',
                        'imageCollection' => [
                            0 => 'composer.json',
                        ],
                    ],
                ],
                'type' => [
                    'newExample' => [
                        'image' => 'image/jpeg',
                        'imageCollection' => [
                            0 => 'application/json',
                        ],
                    ],
                ],
                'tmp_name' => [
                    'newExample' => [
                        'image' => '/Applications/MAMP/tmp/php/phphXdbcd',
                        'imageCollection' => [
                            0 => '/Applications/MAMP/tmp/php/phpgrZ4bb',
                        ],
                    ],
                ],
                'error' => [
                    'newExample' => [
                        'image' => 0,
                        'imageCollection' => [
                            0 => 0,
                        ],
                    ],
                ],
                'size' => [
                    'newExample' => [
                        'image' => 59065,
                        'imageCollection' => [
                            0 => 683,
                        ],
                    ],
                ],
            ],
        ];

        $uploadedFiles = ServerRequestFactory::fromGlobals()->getUploadedFiles();

        self::assertNotEmpty($uploadedFiles['tx_uploadexample_piexample']['newExample']['image']);
        self::assertInstanceOf(UploadedFile::class, $uploadedFiles['tx_uploadexample_piexample']['newExample']['image']);
        self::assertNotEmpty($uploadedFiles['tx_uploadexample_piexample']['newExample']['imageCollection'][0]);
        self::assertInstanceOf(
            UploadedFile::class,
            $uploadedFiles['tx_uploadexample_piexample']['newExample']['imageCollection'][0]
        );
    }

    #[Test]
    public function uploadedFilesAreNotCreatedForEmptyFilesArray(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER['REMOTE_ADDR'] = '';
        $_SERVER['SSL_SESSION_ID'] = '';
        $_FILES = [];

        $uploadedFiles = ServerRequestFactory::fromGlobals()->getUploadedFiles();

        self::assertEmpty($uploadedFiles);
    }

    #[Test]
    public function uploadedFilesAreNotCreatedIfTmpNameIsEmpty(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER['REMOTE_ADDR'] = '';
        $_SERVER['SSL_SESSION_ID'] = '';
        $_FILES = [
            'tx_uploadexample_piexample' => [
                'name' => '',
                'tmp_name' => '',
                'error' => 4,
                'size' => 0,
            ],
        ];

        $uploadedFiles = ServerRequestFactory::fromGlobals()->getUploadedFiles();

        self::assertEmpty($uploadedFiles);
    }

    #[Test]
    public function handlesNumericKeys(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER[1] = '1';

        $request = ServerRequestFactory::fromGlobals();

        self::assertEquals([], $request->getHeader('1'), 'Numeric keys are not processed, default empty array should be returned.');
    }
}
