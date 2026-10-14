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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\Container;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Form\Container\FilesControlContainer;
use TYPO3\CMS\Backend\Form\Event\CustomFileControlsEvent;
use TYPO3\CMS\Backend\Form\Event\CustomFileSelectorsEvent;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter;
use TYPO3\CMS\Core\Resource\Event\AfterDefaultUploadFolderWasResolvedEvent;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Schema\SchemaCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FilesControlContainerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/files-control-container-test', true);
        parent::tearDown();
    }

    #[Test]
    public function customFileControlsEventIsCalled(): void
    {
        $customFileControlsEvent = null;
        $controls = ['foo', 'bar'];
        $databaseRow = [
            'uid' => 123,
        ];
        $fieldConfig = [
            'minitems' => 1,
            'maxitems' => 2,
        ];
        $fieldName = 'assets';
        $tableName = 'tx_table';
        $formFieldIdentifier = 'data-123-tx_table-123-assets';
        $formFieldName = 'data[tx_table][123][assets]';

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'custom-file-controls-listener',
            static function (CustomFileControlsEvent $event) use (&$customFileControlsEvent, $controls) {
                $customFileControlsEvent = $event;
                $event->setControls($controls);
                $event->setResultArray(['javaScriptModules' => ['fooJavaScriptModule']]);
            }
        );

        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(CustomFileControlsEvent::class, 'custom-file-controls-listener');

        $subject = $this->get(FilesControlContainer::class);
        $subject->setData([
            'inlineData' => [],
            'inlineStructure' => [],
            'inlineFirstPid' => 123,
            'fieldName' => $fieldName,
            'tableName' => $tableName,
            'renderType' => 'file',
            'databaseRow' => $databaseRow,
            'tabAndInlineStack' => '',
            'request' => new ServerRequest()
                ->withAttribute('route', new Route('', []))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE),
            'parameterArray' => [
                'itemFormElName' => '',
                'fieldConf' => [
                    'label' => 'foobar',
                    'config' => $fieldConfig,
                    'children' => [],
                ],
            ],
            'returnUrl' => '',
            'tcaSchemata' => new SchemaCollection([]),
        ]);
        $result = $subject->render();

        self::assertInstanceOf(CustomFileControlsEvent::class, $customFileControlsEvent);
        self::assertContains('fooJavaScriptModule', $result['javaScriptModules']);
        self::assertContains('fooJavaScriptModule', $customFileControlsEvent->getResultArray()['javaScriptModules']);
        self::assertEquals($controls, $customFileControlsEvent->getControls());
        self::assertEquals($databaseRow, $customFileControlsEvent->getDatabaseRow());
        self::assertArrayHasKey('minitems', $customFileControlsEvent->getFieldConfig());
        self::assertArrayHasKey('maxitems', $customFileControlsEvent->getFieldConfig());
        self::assertEquals($fieldName, $customFileControlsEvent->getFieldName());
        self::assertEquals($formFieldIdentifier, $customFileControlsEvent->getFormFieldIdentifier());
        self::assertEquals($formFieldName, $customFileControlsEvent->getFormFieldName());
        self::assertEquals($tableName, $customFileControlsEvent->getTableName());
    }

    #[Test]
    public function customFileSelectorsEventIsCalled(): void
    {
        $customFileSelectorsEvent = null;
        $selectors = ['<button>selector1</button>', '<button>selector2</button>'];
        $formFieldIdentifier = 'data-123-tx_table-123-assets-sys_file_reference';

        $container = $this->get('service_container');
        $container->set(
            'custom-file-selectors-listener',
            static function (CustomFileSelectorsEvent $event) use (&$customFileSelectorsEvent, $selectors) {
                $customFileSelectorsEvent = $event;
                $event->setSelectors(array_merge($selectors, $event->getSelectors()));
                $event->setJavascriptModules(['myCustomJavasScriptModule']);
            }
        );

        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(CustomFileSelectorsEvent::class, 'custom-file-selectors-listener');

        $subject = $this->get(FilesControlContainer::class);
        $subject->setData([
            'inlineData' => [],
            'inlineStructure' => [],
            'inlineFirstPid' => 123,
            'fieldName' => 'assets',
            'tableName' => 'my_table',
            'renderType' => 'file',
            'databaseRow' => [
                'uid' => 123,
            ],
            'tabAndInlineStack' => '',
            'request' => new ServerRequest()
                ->withAttribute('route', new Route('', []))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE),
            'parameterArray' => [
                'itemFormElName' => '',
                'fieldConf' => [
                    'label' => 'foobar',
                    'config' => [],
                    'children' => [],
                ],
            ],
            'returnUrl' => '',
            'tcaSchemata' => new SchemaCollection([]),
        ]);
        $result = $subject->render();

        self::assertContains('myCustomJavasScriptModule', $result['javaScriptModules']);
        self::assertEquals(3, count($customFileSelectorsEvent->getSelectors()));
    }

    #[Test]
    public function allowedFileExtensionsAreNarrowedToTheUploadFolder(): void
    {
        $result = $this->renderWithUploadFolder(['allowed' => 'jpg,gif,pdf'], 'jpg,png');

        self::assertStringContainsString('<span class="badge badge-secondary">jpg</span>', $result);
        self::assertStringNotContainsString('<span class="badge badge-secondary">gif</span>', $result);
        self::assertStringNotContainsString('<span class="badge badge-secondary">png</span>', $result);
        self::assertStringContainsString('data-file-allowed="jpg"', $result);
    }

    #[Test]
    public function allowedFileExtensionsOfTheUploadFolderAreShownIfTheFieldAllowsAll(): void
    {
        $result = $this->renderWithUploadFolder(['disallowed' => 'png'], 'jpg,png');

        self::assertStringContainsString('<span class="badge badge-secondary">jpg</span>', $result);
        self::assertStringNotContainsString('>png</span>', $result);
        self::assertStringContainsString('data-file-allowed="jpg"', $result);
    }

    #[Test]
    public function uploadIsOmittedIfTheFieldAllowsNoFileExtensionAllowedInTheUploadFolder(): void
    {
        $result = $this->renderWithUploadFolder(['allowed' => 'pdf'], 'jpg');

        self::assertStringNotContainsString('t3js-drag-uploader', $result);
        self::assertStringContainsString('t3js-element-browser', $result);
    }

    #[Test]
    public function onlineMediaIsNarrowedToTheUploadFolder(): void
    {
        $result = $this->renderWithUploadFolder([], 'youtube,jpg');

        self::assertStringContainsString('data-online-media-allowed="youtube"', $result);
    }

    #[Test]
    public function onlineMediaFileExtensionsOfTheUploadFolderAreNotOfferedForUpload(): void
    {
        $result = $this->renderWithUploadFolder([], 'youtube,jpg');

        self::assertStringContainsString('data-file-allowed="jpg"', $result);
        self::assertStringContainsString('data-online-media-allowed="youtube"', $result);
    }

    #[Test]
    public function uploadIsOmittedIfOnlyOnlineMediaFileExtensionsAreAllowedInTheUploadFolder(): void
    {
        $result = $this->renderWithUploadFolder([], 'youtube');

        self::assertStringNotContainsString('t3js-drag-uploader', $result);
        self::assertStringContainsString('data-online-media-allowed="youtube"', $result);
    }

    private function renderWithUploadFolder(array $fieldConfig, string $allowedFileExtensionsInStorage): string
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $uploadFolder = $this->createFolderInStorage($allowedFileExtensionsInStorage);

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'upload-folder-listener',
            static function (AfterDefaultUploadFolderWasResolvedEvent $event) use ($uploadFolder) {
                $event->setUploadFolder($uploadFolder);
            }
        );
        $this->get(ListenerProvider::class)->addListener(AfterDefaultUploadFolderWasResolvedEvent::class, 'upload-folder-listener');

        $subject = $this->get(FilesControlContainer::class);
        $subject->setData([
            'inlineData' => [],
            'inlineStructure' => [],
            'inlineFirstPid' => 123,
            'fieldName' => 'assets',
            'tableName' => 'my_table',
            'renderType' => 'file',
            'databaseRow' => [
                'uid' => 123,
            ],
            'parentPageRow' => [
                'uid' => 123,
            ],
            'tabAndInlineStack' => '',
            'request' => new ServerRequest()
                ->withAttribute('route', new Route('', []))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE),
            'parameterArray' => [
                'itemFormElName' => '',
                'fieldConf' => [
                    'label' => 'foobar',
                    'config' => $fieldConfig,
                    'children' => [],
                ],
            ],
            'returnUrl' => '',
            'tcaSchemata' => new SchemaCollection([]),
        ]);
        return $subject->render()['html'];
    }

    private function createFolderInStorage(string $allowedFileExtensions): Folder
    {
        $basePath = $this->instancePath . '/files-control-container-test/storage';
        GeneralUtility::mkdir_deep($basePath);
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
