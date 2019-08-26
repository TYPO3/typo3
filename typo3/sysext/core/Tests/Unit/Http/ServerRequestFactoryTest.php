<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Http;

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

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Http\ServerRequestFactory
 */
class ServerRequestFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function implementsPsr17FactoryInterface()
    {
        $factory = new ServerRequestFactory();
        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $factory);
    }

    /**
     * @test
     */
    public function testServerRequestHasMethodSet()
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('POST', '/');
        $this->assertSame('POST', $request->getMethod());
    }

    /**
     * @test
     */
    public function testServerRequestFactoryHasAWritableEmptyBody()
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('GET', '/');
        $body = $request->getBody();

        $this->assertInstanceOf(ServerRequestInterface::class, $request);

        $this->assertSame('', $body->__toString());
        $this->assertSame(0, $body->getSize());
        $this->assertTrue($body->isSeekable());

        $body->write('Foo');
        $this->assertSame(3, $body->getSize());
        $this->assertSame('Foo', $body->__toString());
    }

    /**
     * @return array
     */
    public function invalidRequestUriDataProvider()
    {
        return [
            'true'     => [true],
            'false'    => [false],
            'int'      => [1],
            'float'    => [1.1],
            'array'    => [['http://example.com']],
            'stdClass' => [(object)['href' => 'http://example.com']],
        ];
    }

    /**
     * @dataProvider invalidRequestUriDataProvider
     * @test
     */
    public function constructorRaisesExceptionForInvalidUri($uri)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717272);
        $factory = new ServerRequestFactory();
        $factory->createServerRequest('GET', $uri);
    }

    /**
     * @test
     */
    public function raisesExceptionForInvalidMethod()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717275);
        $factory = new ServerRequestFactory();
        $factory->createServerRequest('BOGUS-BODY', '/');
    }

    /**
     * @test
     */
    public function uploadedFilesAreNormalizedFromFilesSuperGlobal()
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
                                0 => 'application/json'
                            ]
                        ]
                    ],
                    'tmp_name' => [
                        'newExample' => [
                            'image' => '/Applications/MAMP/tmp/php/phphXdbcd',
                            'imageCollection' => [
                                0 => '/Applications/MAMP/tmp/php/phpgrZ4bb'
                            ]
                        ]
                    ],
                    'error' => [
                        'newExample' => [
                                'image' => 0,
                                'imageCollection' => [
                                    0 => 0
                                ]
                        ]
                    ],
                    'size' => [
                        'newExample' => [
                            'image' => 59065,
                            'imageCollection' => [
                                0 => 683
                            ]
                        ]
                    ]
            ]
        ];

        $uploadedFiles = ServerRequestFactory::fromGlobals()->getUploadedFiles();

        $this->assertNotEmpty($uploadedFiles['tx_uploadexample_piexample']['newExample']['image']);
        $this->assertTrue($uploadedFiles['tx_uploadexample_piexample']['newExample']['image'] instanceof UploadedFile);
        $this->assertNotEmpty($uploadedFiles['tx_uploadexample_piexample']['newExample']['imageCollection'][0]);
        $this->assertTrue($uploadedFiles['tx_uploadexample_piexample']['newExample']['imageCollection'][0] instanceof UploadedFile);
    }

    /**
     * @test
     */
    public function uploadedFilesAreNotCreatedForEmptyFilesArray()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER['REMOTE_ADDR'] = '';
        $_SERVER['SSL_SESSION_ID'] = '';
        $_FILES = [];

        $uploadedFiles = ServerRequestFactory::fromGlobals()->getUploadedFiles();

        $this->assertEmpty($uploadedFiles);
    }

    /**
     * @test
     */
    public function uploadedFilesAreNotCreatedIfTmpNameIsEmpty()
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

        $this->assertEmpty($uploadedFiles);
    }
}
