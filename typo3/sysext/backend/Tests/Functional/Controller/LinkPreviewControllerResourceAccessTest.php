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
use TYPO3\CMS\Backend\Controller\LinkPreviewController;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Covers the resource related access checks of the "link_preview" AJAX route:
 * the file mount / file permission evaluation and the fallback storage guard.
 */
final class LinkPreviewControllerResourceAccessTest extends FunctionalTestCase
{
    private const FIXTURE_PATH = 'typo3/sysext/backend/Tests/Functional/Controller/Fixtures/LinkPreviewResourceAccess';

    /**
     * @var array<string, non-empty-string>
     */
    protected array $pathsToProvideInTestInstance = [
        self::FIXTURE_PATH . '/Files/allowed.txt' => 'fileadmin/user_upload/allowed.txt',
        self::FIXTURE_PATH . '/Files/secret.txt' => 'fileadmin/restricted/secret.txt',
        self::FIXTURE_PATH . '/Files/outside.txt' => 'typo3conf/outside.txt',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LinkPreviewResourceAccess/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LinkPreviewResourceAccess/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LinkPreviewResourceAccess/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LinkPreviewResourceAccess/sys_filemounts.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LinkPreviewResourceAccess/sys_file.csv');
    }

    #[Test]
    public function fileInsideFileMountIsResolved(): void
    {
        $this->setUpBackendRequestAndUser(2);

        $result = $this->resolve('t3://file?uid=1');

        self::assertSame('file', $result['type']);
        self::assertSame('allowed.txt', $result['title']);
        self::assertSame('1:/user_upload/allowed.txt', $result['path']);
        self::assertStringContainsString('fileadmin/user_upload/allowed.txt', (string)$result['url']);
    }

    #[Test]
    public function fileOutsideFileMountIsNotDisclosed(): void
    {
        $this->setUpBackendRequestAndUser(2);

        $result = $this->resolve('t3://file?uid=2');

        self::assertSame('file', $result['type']);
        self::assertNull($result['title']);
        self::assertNull($result['path']);
        self::assertNull($result['url']);
    }

    /**
     * The fallback storage (uid 0) is rooted at the public path and is exempt from
     * permission evaluation, so an admin passes every FAL check here. Only the
     * explicit isFallbackStorage() guard keeps the file out of the response.
     */
    #[Test]
    public function fileInFallbackStorageIsNotDisclosedToAdmin(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $result = $this->resolve('t3://file?uid=3');

        self::assertSame('file', $result['type']);
        self::assertNull($result['title']);
        self::assertNull($result['path']);
        self::assertNull($result['url']);
    }

    #[Test]
    public function folderInsideFileMountIsResolved(): void
    {
        $this->setUpBackendRequestAndUser(2);

        $result = $this->resolve('t3://folder?storage=1&identifier=/user_upload/');

        self::assertSame('folder', $result['type']);
        self::assertSame('user_upload', $result['title']);
        self::assertSame('1:/user_upload/', $result['path']);
    }

    #[Test]
    public function folderOutsideFileMountIsNotDisclosed(): void
    {
        $this->setUpBackendRequestAndUser(2);

        $result = $this->resolve('t3://folder?storage=1&identifier=/restricted/');

        self::assertSame('folder', $result['type']);
        self::assertNull($result['title']);
        self::assertNull($result['path']);
    }

    #[Test]
    public function folderInFallbackStorageIsNotDisclosedToAdmin(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $result = $this->resolve('t3://folder?storage=0&identifier=/typo3conf/');

        self::assertSame('folder', $result['type']);
        self::assertNull($result['title']);
        self::assertNull($result['path']);
    }

    private function resolve(string $href): array
    {
        $request = $this->createBackendRequest()->withQueryParams(['href' => $href]);
        $response = $this->get(LinkPreviewController::class)->resolveAction($request);
        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * The storage permission aspect only evaluates file mounts when it sees a backend
     * request in $GLOBALS['TYPO3_REQUEST'], and it does so while the storage object is
     * built. The request therefore has to be in place before the first resolve() call.
     */
    private function setUpBackendRequestAndUser(int $userUid): void
    {
        $GLOBALS['TYPO3_REQUEST'] = $this->createBackendRequest();
        $backendUser = $this->setUpBackendUser($userUid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    private function createBackendRequest(): ServerRequest
    {
        return new ServerRequest('https://example.com/typo3/ajax/link/preview')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE | SystemEnvironmentBuilder::REQUESTTYPE_AJAX);
    }
}
