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

namespace TYPO3\CMS\Filelist\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Filelist\Controller\FileDownloadController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileDownloadControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['filelist'];

    /**
     * @var array<string, non-empty-string>
     */
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/filelist/Tests/Functional/Fixtures/textfile.txt' => 'fileadmin/textfile.txt',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function handleRequestExitsWithErrorResponseWhenNoItemsGiven(): void
    {
        $parsedBody = [];
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withParsedBody($parsedBody);
        $response = $this->get(FileDownloadController::class)->handleRequest($request);
        self::assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function handleRequestReturnsNoFilesWhenFileNotFound(): void
    {
        $parsedBody = [
            'items' => ['non-existing-file.txt'],
        ];
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withParsedBody($parsedBody);
        $response = $this->get(FileDownloadController::class)->handleRequest($request);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);
        self::assertFalse($jsonArray['success']);
        self::assertSame('noFiles', $jsonArray['status']);
    }

    #[Test]
    public function handleRequestReturnsFileAsZipWhenFileExists(): void
    {
        $parsedBody = [
            'items' => ['1:/textfile.txt'],
        ];
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withParsedBody($parsedBody);
        $response = $this->get(FileDownloadController::class)->handleRequest($request);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/zip', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('attachment; filename=typo3_download_', $response->getHeaderLine('Content-Disposition'));
    }

    #[Test]
    public function handleRequestReturnsNoFilesWhenFilesInFallbackStorage(): void
    {
        $parsedBody = [
            'items' => ['typo3temp/var/log/', '.htpasswd'],
        ];
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withParsedBody($parsedBody);
        $response = $this->get(FileDownloadController::class)->handleRequest($request);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);
        self::assertFalse($jsonArray['success']);
        self::assertSame('noFiles', $jsonArray['status']);
    }

    #[Test]
    public function handleRequestDeniesDownloadWhenDownloadIsDisabledForUser(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $parsedBody = [
            'items' => ['1:/textfile.txt'],
        ];
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withParsedBody($parsedBody);
        $response = $this->get(FileDownloadController::class)->handleRequest($request);
        self::assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function handleRequestReturnsNoFilesWhenFileExistsButDownloadOfGivenFileExtensionIsNotInAllowList(): void
    {
        $backendUser = $this->setUpBackendUser(3);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $parsedBody = [
            'items' => ['1:/textfile.txt'],
        ];
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withParsedBody($parsedBody);
        $response = $this->get(FileDownloadController::class)->handleRequest($request);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);
        self::assertFalse($jsonArray['success']);
        self::assertSame('noFiles', $jsonArray['status']);
    }

    #[Test]
    public function handleRequestReturnsNoFilesWhenFileExistsButDownloadOfGivenFileExtensionIsInDenyList(): void
    {
        $backendUser = $this->setUpBackendUser(4);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $parsedBody = [
            'items' => ['1:/textfile.txt'],
        ];
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withParsedBody($parsedBody);
        $response = $this->get(FileDownloadController::class)->handleRequest($request);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $body = (string)$response->getBody();
        $jsonArray = json_decode($body, true);
        self::assertFalse($jsonArray['success']);
        self::assertSame('noFiles', $jsonArray['status']);
    }
}
