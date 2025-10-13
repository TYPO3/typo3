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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\FileStorage;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Controller\Event\AfterFileStorageTreeItemsPreparedEvent;
use TYPO3\CMS\Backend\Controller\FileStorage\TreeController;
use TYPO3\CMS\Backend\Dto\Tree\Label\Label;
use TYPO3\CMS\Backend\Dto\Tree\Status\StatusInformation;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TreeControllerTest extends FunctionalTestCase
{
    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_file_storage.csv');

        $this->backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);

        // Create physical directories for storages
        $this->createStorageDirectories();
    }

    private function createStorageDirectories(): void
    {
        $basePath = $this->instancePath . '/';
        foreach (['fileadmin', 'fileadmin/user_upload', 'user_upload'] as $folder) {
            if (!file_exists($basePath . $folder)) {
                mkdir($basePath . $folder, 0777, true);
            }
        }
    }

    #[Test]
    public function fileStorageTreeItemsModificationEventIsTriggered(): void
    {
        $afterFileStorageTreeItemsPreparedEvent = null;
        $label = new Label(
            label: 'foo',
            color: '#abcdef',
            priority: 10
        );
        $statusInformation = new StatusInformation(
            label: 'bar',
            severity: ContextualFeedbackSeverity::INFO,
            priority: 10,
            icon: 'actions-document'
        );

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-file-storage-tree-items-prepared-listener',
            static function (AfterFileStorageTreeItemsPreparedEvent $event) use (&$afterFileStorageTreeItemsPreparedEvent, $label, $statusInformation) {
                $items = $event->getItems();
                foreach ($items as &$item) {
                    if ($item['resource']->getCombinedIdentifier() === '1:/user_upload/') {
                        $item['labels'][] = $label;
                        $item['statusInformation'][] = $statusInformation;
                    }
                }
                $event->setItems($items);
                $afterFileStorageTreeItemsPreparedEvent = $event;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterFileStorageTreeItemsPreparedEvent::class, 'after-file-storage-tree-items-prepared-listener');

        $request = new ServerRequest(new Uri('https://example.com'));

        $this->get(TreeController::class)->fetchDataAction($request);

        self::assertInstanceOf(AfterFileStorageTreeItemsPreparedEvent::class, $afterFileStorageTreeItemsPreparedEvent);
        self::assertEquals($request, $afterFileStorageTreeItemsPreparedEvent->getRequest());
        self::assertNotEmpty($afterFileStorageTreeItemsPreparedEvent->getItems());

        $items = $afterFileStorageTreeItemsPreparedEvent->getItems();
        foreach ($items as $item) {
            if ($item['resource']->getCombinedIdentifier() === '1:/user_upload/') {
                self::assertEquals($item['identifier'], urlencode('1:/user_upload/'));
                self::assertEquals($item['resourceType'], 'folder');
                self::assertEquals($item['labels'][0], $label);
                self::assertEquals($item['statusInformation'][0], $statusInformation);
            }
        }
    }

    #[Test]
    public function fileStorageTreeItemsCanBeModifiedByEvent(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'modify-file-storage-tree-items-listener',
            static function (AfterFileStorageTreeItemsPreparedEvent $event) {
                $items = $event->getItems();
                foreach ($items as &$item) {
                    $item['name'] = 'Modified: ' . $item['name'];
                }
                $event->setItems($items);
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterFileStorageTreeItemsPreparedEvent::class, 'modify-file-storage-tree-items-listener');

        $request = new ServerRequest(new Uri('https://example.com'));
        $response = $this->get(TreeController::class)->fetchDataAction($request);

        $data = json_decode((string)$response->getBody(), true);

        foreach ($data as $item) {
            self::assertStringStartsWith('Modified: ', $item['name']);
        }
    }

    #[Test]
    public function tsconfigLabelIsAppliedToFolderTreeItem(): void
    {
        // Use backend user with TSconfig that sets a label for storage 1
        $this->backendUser = $this->setUpBackendUser(3);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);

        $request = new ServerRequest(new Uri('https://example.com'));
        $response = $this->get(TreeController::class)->fetchDataAction($request);

        $data = json_decode((string)$response->getBody(), true);

        // Find the storage item with identifier '1:/'
        $targetItem = null;
        foreach ($data as $item) {
            if ($item['identifier'] === rawurlencode('1:/')) {
                $targetItem = $item;
                break;
            }
        }

        self::assertNotNull($targetItem);
        self::assertIsArray($targetItem['labels']);
        self::assertNotEmpty($targetItem['labels']);

        // Check the first label
        $label = $targetItem['labels'][0];
        self::assertEquals('Main Storage', $label['label']);
        self::assertEquals('#ff0000', $label['color']);
    }

    #[Test]
    public function tsconfigLabelWithDefaultColorIsAppliedToFolderTreeItem(): void
    {
        // Use backend user with TSconfig that sets a label for storage 2 without color
        $this->backendUser = $this->setUpBackendUser(4);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);

        $request = new ServerRequest(new Uri('https://example.com'));
        $response = $this->get(TreeController::class)->fetchDataAction($request);

        $data = json_decode((string)$response->getBody(), true);

        // Find the storage item with identifier '2:/'
        $targetItem = null;
        foreach ($data as $item) {
            if ($item['identifier'] === rawurlencode('2:/')) {
                $targetItem = $item;
                break;
            }
        }

        self::assertNotNull($targetItem, 'Storage with identifier 2:/ should exist');
        self::assertIsArray($targetItem['labels']);
        self::assertNotEmpty($targetItem['labels']);

        // Check the first label
        $label = $targetItem['labels'][0];
        self::assertEquals('User Upload Storage', $label['label']);
        // Default color should be '#ff8722'
        self::assertEquals('#ff8722', $label['color']);
    }

    #[Test]
    public function tsconfigLabelIsNotAppliedWhenEmpty(): void
    {
        // Use backend user with TSconfig that has an empty label
        $this->backendUser = $this->setUpBackendUser(5);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);

        $request = new ServerRequest(new Uri('https://example.com'));
        $response = $this->get(TreeController::class)->fetchDataAction($request);

        $data = json_decode((string)$response->getBody(), true);

        // Find the storage item with identifier '1%3A%2F' (URL-encoded '1:/')
        $targetItem = null;
        foreach ($data as $item) {
            if ($item['identifier'] === rawurlencode('1:/')) {
                $targetItem = $item;
                break;
            }
        }

        self::assertNotNull($targetItem, 'Storage with identifier 1:/ should exist');
        // Labels array should be empty when label is empty string
        self::assertEmpty($targetItem['labels']);
    }

    #[Test]
    public function fetchDataActionReturnsFileStoragesAndFolders(): void
    {
        $request = new ServerRequest(new Uri('https://example.com'));
        $response = $this->get(TreeController::class)->fetchDataAction($request);

        $data = json_decode((string)$response->getBody(), true);

        self::assertIsArray($data);
        self::assertNotEmpty($data);

        // Verify that we have storages in the response
        $storageItems = array_filter($data, static fn(array $item): bool => $item['resourceType'] === 'storage');
        self::assertNotEmpty($storageItems, 'Should have at least one storage');

        // Verify structure of storage items
        self::assertEquals(urlencode('1:/'), $storageItems[0]['identifier']);
        self::assertEquals('fileadmin', $storageItems[0]['name']);
        self::assertEquals('apps-filetree-mount', $storageItems[0]['icon']);
        self::assertEquals('storage', $storageItems[0]['resourceType']);
        self::assertEquals(1, $storageItems[0]['storage']);
    }
}
