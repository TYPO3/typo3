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

use GuzzleHttp\Promise\FulfilledPromise;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Controller\OnlineMediaController;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Covers the "online_media" route and the "online_media_create" AJAX route.
 * The oEmbed request of the online media helpers is answered by a stubbed
 * HTTP handler, everything else runs against the real file storage.
 */
final class OnlineMediaControllerTest extends FunctionalTestCase
{
    private const YOUTUBE_URL = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

    /**
     * @var list<string>
     */
    private array $requestedUrls = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OnlineMedia/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OnlineMedia/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OnlineMedia/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OnlineMedia/sys_filemounts.csv');
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/user_upload/');
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/restricted/');
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['oembed'] = function () {
            return function (RequestInterface $request) {
                $this->requestedUrls[] = (string)$request->getUri();
                return new FulfilledPromise(new JsonResponse(['title' => 'Example']));
            };
        };
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir(Environment::getPublicPath() . '/fileadmin', true);
        mkdir(Environment::getPublicPath() . '/fileadmin');
        parent::tearDown();
    }

    #[Test]
    public function createActionReturnsEmptyResponseWithoutUrl(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $result = $this->create('', '1:/user_upload/');

        self::assertSame([], $result);
        self::assertSame([], $this->requestedUrls);
    }

    #[Test]
    public function createActionReturnsErrorForUnsupportedUrl(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $result = $this->create('https://example.com/no-media', '1:/user_upload/');

        self::assertArrayNotHasKey('file', $result);
        self::assertSame('Unknown/not allowed URL', $result['error']);
    }

    #[Test]
    public function createActionReturnsErrorIfMediaTypeIsNotAllowed(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $result = $this->create(self::YOUTUBE_URL, '1:/user_upload/', 'vimeo');

        self::assertArrayNotHasKey('file', $result);
        self::assertArrayHasKey('error', $result);
        self::assertSame([], $this->requestedUrls);
    }

    #[Test]
    public function createActionAddsMediaToTargetFolder(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $result = $this->create(self::YOUTUBE_URL, '1:/user_upload/');

        $file = $this->get(ResourceFactory::class)->getFileObject($result['file']);
        self::assertSame('1:/user_upload/Example.youtube', $file->getCombinedIdentifier());
        self::assertSame('dQw4w9WgXcQ', $file->getContents());
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/user_upload/Example.youtube');
        self::assertNotEmpty($this->requestedUrls);
        foreach ($this->requestedUrls as $requestedUrl) {
            self::assertStringStartsWith('https://www.youtube.com/oembed?', $requestedUrl);
        }
    }

    #[Test]
    public function createActionReturnsExistingFileForMediaAddedBefore(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $first = $this->create(self::YOUTUBE_URL, '1:/user_upload/');
        $numberOfRequests = count($this->requestedUrls);
        $second = $this->create('https://youtu.be/dQw4w9WgXcQ', '1:/user_upload/');

        self::assertSame($first['file'], $second['file']);
        self::assertCount($numberOfRequests, $this->requestedUrls);
    }

    #[Test]
    public function createActionAddsMediaToDefaultUploadFolderWithoutTargetFolder(): void
    {
        $this->setUpBackendRequestAndUser(2);

        $result = $this->create(self::YOUTUBE_URL, '');

        $file = $this->get(ResourceFactory::class)->getFileObject($result['file']);
        self::assertSame('1:/user_upload/Example.youtube', $file->getCombinedIdentifier());
    }

    #[Test]
    public function createActionAddsMediaToDefaultUploadFolderForNonExistingTargetFolder(): void
    {
        $this->setUpBackendRequestAndUser(2);

        $result = $this->create(self::YOUTUBE_URL, '1:/does-not-exist/');

        $file = $this->get(ResourceFactory::class)->getFileObject($result['file']);
        self::assertSame('1:/user_upload/Example.youtube', $file->getCombinedIdentifier());
    }

    public static function fallbackStorageTargetFolderDataProvider(): \Generator
    {
        yield 'admin, public root' => [1, '0:/'];
        yield 'admin, typo3conf' => [1, '0:/typo3conf/'];
        yield 'editor, public root' => [2, '0:/'];
        yield 'editor, typo3conf' => [2, '0:/typo3conf/'];
    }

    /**
     * Files must never be written to the public web root through the
     * (removed) fallback storage, see #110642.
     */
    #[DataProvider('fallbackStorageTargetFolderDataProvider')]
    #[Test]
    public function createActionDoesNotAddMediaToFallbackStorage(int $userUid, string $targetFolder): void
    {
        $this->setUpBackendRequestAndUser($userUid);

        $result = $this->create(self::YOUTUBE_URL, $targetFolder);

        $file = $this->get(ResourceFactory::class)->getFileObject($result['file']);
        self::assertSame('1:/user_upload/Example.youtube', $file->getCombinedIdentifier());
        self::assertFileDoesNotExist(Environment::getPublicPath() . '/Example.youtube');
        self::assertFileDoesNotExist(Environment::getPublicPath() . '/typo3conf/Example.youtube');
    }

    #[Test]
    public function createActionAddsMediaToDefaultUploadFolderForTargetFolderOutsideOfFileMounts(): void
    {
        $this->setUpBackendRequestAndUser(2);

        $result = $this->create(self::YOUTUBE_URL, '1:/restricted/');

        $file = $this->get(ResourceFactory::class)->getFileObject($result['file']);
        self::assertSame('1:/user_upload/Example.youtube', $file->getCombinedIdentifier());
        self::assertFileDoesNotExist(Environment::getPublicPath() . '/fileadmin/restricted/Example.youtube');
    }

    #[Test]
    public function mainActionAddsMediaAndRedirects(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $response = $this->main([
            ['url' => self::YOUTUBE_URL, 'target' => '1:/user_upload/', 'allowed' => 'youtube'],
        ], 'https://example.com/typo3/module/file/list');

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('https://example.com/typo3/module/file/list', $response->getHeaderLine('Location'));
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/user_upload/Example.youtube');
        $messages = $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages();
        self::assertCount(1, $messages);
        self::assertSame(ContextualFeedbackSeverity::OK, $messages[0]->getSeverity());
        self::assertSame('Example.youtube', $messages[0]->getMessage());
    }

    #[Test]
    public function mainActionUsesRedirectOfMediaEntry(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $response = $this->main([
            ['url' => self::YOUTUBE_URL, 'target' => '1:/user_upload/', 'redirect' => 'https://example.com/typo3/module/file/list?id=1:/user_upload/'],
        ], '');

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('https://example.com/typo3/module/file/list?id=1:/user_upload/', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function mainActionAddsWarningForMediaAddedBefore(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $this->main([['url' => self::YOUTUBE_URL, 'target' => '1:/user_upload/']], 'https://example.com/typo3/');
        $this->main([['url' => self::YOUTUBE_URL, 'target' => '1:/user_upload/']], 'https://example.com/typo3/');

        $messages = $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages();
        self::assertCount(2, $messages);
        self::assertSame(ContextualFeedbackSeverity::WARNING, $messages[1]->getSeverity());
        self::assertStringContainsString('Example.youtube', $messages[1]->getMessage());
    }

    #[Test]
    public function mainActionAddsErrorForUnsupportedUrl(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $response = $this->main([['url' => 'https://example.com/no-media', 'target' => '1:/user_upload/']], 'https://example.com/typo3/');

        self::assertSame(303, $response->getStatusCode());
        $messages = $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages();
        self::assertCount(1, $messages);
        self::assertSame(ContextualFeedbackSeverity::ERROR, $messages[0]->getSeverity());
    }

    #[Test]
    public function mainActionDoesNotAddMediaToFallbackStorage(): void
    {
        $this->setUpBackendRequestAndUser(2);

        $this->main([['url' => self::YOUTUBE_URL, 'target' => '0:/']], 'https://example.com/typo3/');

        self::assertFileDoesNotExist(Environment::getPublicPath() . '/Example.youtube');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/user_upload/Example.youtube');
    }

    #[Test]
    public function mainActionThrowsExceptionWithoutRedirect(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1511945040);
        $this->main([['url' => self::YOUTUBE_URL, 'target' => '1:/user_upload/', 'redirect' => '']], '');
    }

    #[Test]
    public function mainActionDoesNotRedirectToExternalUrl(): void
    {
        $this->setUpBackendRequestAndUser(1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1511945040);
        $this->main([], 'https://evil.example.org/');
    }

    private function create(string $url, string $targetFolder, string $allowed = 'youtube,vimeo'): array
    {
        $request = $this->createBackendRequest()->withParsedBody([
            'url' => $url,
            'targetFolder' => $targetFolder,
            'allowed' => $allowed,
        ]);
        $response = $this->get(OnlineMediaController::class)->createAction($request);
        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function main(array $newMedia, string $redirect): ResponseInterface
    {
        $request = $this->createBackendRequest()->withParsedBody([
            'data' => ['newMedia' => $newMedia],
            'redirect' => $redirect,
        ]);
        return $this->get(OnlineMediaController::class)->mainAction($request);
    }

    /**
     * The storage permission aspect only evaluates file mounts when it sees a backend
     * request in $GLOBALS['TYPO3_REQUEST'], and it does so while the storage object is
     * built. The request therefore has to be in place before the first action call.
     */
    private function setUpBackendRequestAndUser(int $userUid): void
    {
        $GLOBALS['TYPO3_REQUEST'] = $this->createBackendRequest();
        $backendUser = $this->setUpBackendUser($userUid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    private function createBackendRequest(): ServerRequest
    {
        return (new ServerRequest('https://example.com/typo3/ajax/online-media/create', 'POST'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE | SystemEnvironmentBuilder::REQUESTTYPE_AJAX)
            ->withAttribute('normalizedParams', NormalizedParams::createFromServerParams(['HTTP_HOST' => 'example.com', 'HTTPS' => 'on']));
    }
}
