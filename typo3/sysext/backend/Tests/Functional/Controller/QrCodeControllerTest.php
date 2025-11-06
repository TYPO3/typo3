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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\QrCodeController;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\MimeTypeDetector;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class QrCodeControllerTest extends FunctionalTestCase
{
    protected QrCodeController $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->subject = new QrCodeController(new ResponseFactory(), new StreamFactory(), new MimeTypeDetector(), new GraphicalFunctions());
    }

    #[Test]
    public function getQrCodeTest(): void
    {
        $request = (new ServerRequest('https://example.com/typo3/', 'GET'))->withQueryParams([
            'size' => 'large',
            'content' => 'https://www.example.com',
        ]);

        $response = $this->subject->getQrCodeAction($request);

        self::assertEquals(file_get_contents(__DIR__ . '/../Fixtures/qrcode-test.svg'), (string)$response->getBody());
    }

    #[Test]
    public function getQrCodeThrowsExceptionOnEmptyContentTest(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $request = (new ServerRequest('https://example.com/typo3/', 'GET'))->withQueryParams([
            'size' => 'large',
            'content' => '',
        ]);

        $this->subject->getQrCodeAction($request);
    }

    #[Test]
    public function downloadTest(): void
    {
        $request = (new ServerRequest('https://example.com/typo3/', 'POST'))->withParsedBody([
            'size' => 'large',
            'format' => 'png',
            'content' => 'https://www.example.com',
        ]);
        $response = $this->subject->downloadAction($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(8459, $response->getHeader('content-length')[0]);
        self::assertSame('image/png', $response->getHeader('content-type')[0]);
        self::assertSame(
            'attachment; filename="qrcode-256px-d83a703bfb0e98e2f2f9b0dbd9a2633bb4fe4ea9.png"',
            $response->getHeader('content-disposition')[0]
        );
    }

    #[Test]
    public function downloadThrowsExceptionOnInvalidFormatTest(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $request = (new ServerRequest('https://example.com/typo3/', 'POST'))->withParsedBody([
            'format' => 'jpg',
            'content' => 'https://www.example.com',
        ]);

        $this->subject->downloadAction($request);
    }
}
