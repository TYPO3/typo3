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

namespace TYPO3\CMS\Filelist\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Filelist\Event\AfterFileListRowPreparedEvent;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\CMS\Filelist\Type\SortDirection;
use TYPO3\CMS\Filelist\Type\ViewMode;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileListTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_file_storage.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function afterFileListRowPreparedEventIsTriggered(): void
    {
        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'after-file-list-row-prepared-event-listener',
            static function (AfterFileListRowPreparedEvent $event) {
                $data = $event->getData();
                $data['name'] = 'NEW NAME';
                $event->setData($data);

                $attributes = $event->getAttributes();
                $attributes['data-custom-marker'] = 'custom-marker-value';
                $event->setAttributes($attributes);
            }
        );

        /** @var ListenerProviderInterface&ListenerProvider $listenerProvider */
        $listenerProvider = $this->get(ListenerProvider::class);
        $listenerProvider->addListener(AfterFileListRowPreparedEvent::class, 'after-file-list-row-prepared-event-listener');
        $container->set(EventDispatcherInterface::class, new EventDispatcher($listenerProvider));

        $request = new ServerRequest('http://localhost/');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $request = $request->withAttribute('route', new Route('/module/file/list', ['_identifier' => 'media_management']));
        $folder = $this->get(StorageRepository::class)->findByUid(1)->getFolder('/');

        $fileList = GeneralUtility::makeInstance(FileList::class, $request);
        $fileList->viewMode = ViewMode::LIST;
        $fileList->start($folder, 1, 'name', SortDirection::ASCENDING);

        $view = new class implements ViewInterface {
            private array $values = [];

            public function assign(string $key, mixed $value): self
            {
                $this->values[$key] = $value;
                return $this;
            }

            public function assignMultiple(array $values): self
            {
                $this->values = array_merge($this->values, $values);
                return $this;
            }

            public function render(string $templateFileName = ''): string
            {
                return (string)($this->values['tableBody'] ?? '');
            }
        };

        $listHtml = $fileList->render(null, $view);

        self::assertStringContainsString('NEW NAME', $listHtml);
        self::assertStringContainsString('custom-marker-value', $listHtml);
    }
}
