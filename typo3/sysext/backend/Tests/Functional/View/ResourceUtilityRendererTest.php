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

namespace TYPO3\CMS\Backend\Tests\Functional\View;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Backend\View\ResourceUtilityRenderer;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ResourceUtilityRendererTest extends FunctionalTestCase
{
    private ResourceUtilityRenderer $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        mkdir($this->instancePath . '/resource-utility-renderer-test');
        $this->subject = new ResourceUtilityRenderer(new class implements LinkParameterProviderInterface {
            public function getUrlParameters(array $values): array
            {
                return $values;
            }
        });
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/resource-utility-renderer-test', true);
        parent::tearDown();
    }

    #[Test]
    public function createRegularFileListsTextFileExtensionsAllowedInFolder(): void
    {
        $result = $this->subject->createRegularFile($this->createRequest(), $this->createFolderInStorage('txt,jpg'));

        self::assertStringContainsString('>txt<', $result);
        self::assertStringNotContainsString('>html<', $result);
    }

    #[Test]
    public function createRegularFileIsOmittedIfNoTextFileExtensionIsAllowedInFolder(): void
    {
        self::assertSame('', $this->subject->createRegularFile($this->createRequest(), $this->createFolderInStorage('mp4')));
    }

    #[Test]
    public function addOnlineMediaListsOnlineMediaFileExtensionsAllowedInFolder(): void
    {
        $result = $this->subject->addOnlineMedia($this->createRequest(), $this->createFolderInStorage('youtube,mp4'));

        self::assertStringContainsString('>youtube<', $result);
        self::assertStringNotContainsString('>vimeo<', $result);
    }

    #[Test]
    public function addOnlineMediaIsOmittedIfNoOnlineMediaFileExtensionIsAllowedInFolder(): void
    {
        self::assertSame('', $this->subject->addOnlineMedia($this->createRequest(), $this->createFolderInStorage('mp4')));
    }

    #[Test]
    public function createDragUploadListsFileExtensionsAllowedInFolder(): void
    {
        $result = $this->subject->createDragUpload($this->createFolderInStorage('mp4,webm'));

        self::assertStringContainsString('>mp4<', $result);
        self::assertStringContainsString('>webm<', $result);
    }

    #[Test]
    public function createDragUploadListsFileExtensionsAllowedInFolderAndByFilter(): void
    {
        $fileExtensionFilter = new FileExtensionFilter();
        $fileExtensionFilter->setAllowedFileExtensions('jpg,pdf,gif');

        $result = $this->subject->createDragUpload($this->createFolderInStorage('jpg,png,pdf'), $fileExtensionFilter);

        self::assertStringContainsString('>jpg<', $result);
        self::assertStringContainsString('>pdf<', $result);
        self::assertStringNotContainsString('>png<', $result);
        self::assertStringNotContainsString('>gif<', $result);
    }

    #[Test]
    public function createDragUploadIsOmittedIfFilterAllowsNoFileExtensionAllowedInFolder(): void
    {
        $fileExtensionFilter = new FileExtensionFilter();
        $fileExtensionFilter->setAllowedFileExtensions('pdf');

        self::assertSame('', $this->subject->createDragUpload($this->createFolderInStorage('jpg'), $fileExtensionFilter));
    }

    #[Test]
    public function createDragUploadOmitsOnlineMediaFileExtensionsAllowedInFolder(): void
    {
        $result = $this->subject->createDragUpload($this->createFolderInStorage('jpg,youtube'));

        self::assertStringContainsString('>jpg<', $result);
        self::assertStringNotContainsString('>youtube<', $result);
    }

    #[Test]
    public function createDragUploadIsOmittedIfOnlyOnlineMediaFileExtensionsAreAllowedInFolder(): void
    {
        self::assertSame('', $this->subject->createDragUpload($this->createFolderInStorage('youtube')));
    }

    #[Test]
    public function uploadFormListsFileExtensionsAllowedInFolder(): void
    {
        $result = $this->subject->uploadForm($this->createRequest(), $this->createFolderInStorage('mp4'));

        self::assertStringContainsString('>mp4<', $result);
    }

    #[Test]
    public function uploadFormOmitsOnlineMediaFileExtensionsAllowedInFolder(): void
    {
        $result = $this->subject->uploadForm($this->createRequest(), $this->createFolderInStorage('jpg,youtube'));

        self::assertStringContainsString('>jpg<', $result);
        self::assertStringNotContainsString('>youtube<', $result);
    }

    private function createRequest(): ServerRequestInterface
    {
        return new ServerRequest()->withAttribute('route', new Route('/file/commit', ['_identifier' => 'tce_file']));
    }

    private function createFolderInStorage(string $allowedFileExtensions): Folder
    {
        $basePath = $this->instancePath . '/resource-utility-renderer-test/storage';
        mkdir($basePath);
        $storageRepository = $this->get(StorageRepository::class);
        $uid = $storageRepository->addLocalStorage('storage', $basePath);
        $this->get(FileStorageConfigurationWriter::class)->write($uid, [
            'name' => 'storage',
            'allowedFileExtensions' => $allowedFileExtensions,
            'configuration' => ['basePath' => $basePath . '/'],
        ]);
        $storageRepository->flush();
        return $storageRepository->findByUid($uid)->getRootLevelFolder();
    }
}
