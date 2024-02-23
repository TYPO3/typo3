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

namespace TYPO3\CMS\Core\Tests\Functional\Package\Event;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageInitializationResultIdentifierException;
use TYPO3\CMS\Core\Package\Initialization\CheckForImportRequirements;
use TYPO3\CMS\Core\Package\Initialization\ImportExtensionDataOnPackageInitialization;
use TYPO3\CMS\Core\Package\Initialization\ImportStaticSqlDataOnPackageInitialization;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageActivationService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PackageInitializationEventTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_package_initialization',
    ];

    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $extensionKey = 'my_ext';
        $package = $this->createMock(Package::class);
        $container = new Container();
        $emitter = $this;

        $event = new PackageInitializationEvent(
            extensionKey: $extensionKey,
            package: $package,
            container: $container,
            emitter: $emitter
        );

        self::assertSame($extensionKey, $event->getExtensionKey());
        self::assertSame($package, $event->getPackage());
        self::assertSame($container, $event->getContainer());
        self::assertSame($emitter, $event->getEmitter());
        self::assertFalse($event->hasStorageEntry(__CLASS__));

        $this->expectException(InvalidPackageInitializationResultIdentifierException::class);
        $this->expectExceptionCode(1706203511);

        $event->getStorageEntry(__CLASS__);
    }

    #[Test]
    public function setterOverwritesResult(): void
    {
        $extensionKey = 'my_ext';
        $package = $this->createMock(Package::class);
        $container = new Container();
        $emitter = $this;

        $event = new PackageInitializationEvent(
            extensionKey: $extensionKey,
            package: $package,
            container: $container,
            emitter: $emitter
        );

        self::assertSame($extensionKey, $event->getExtensionKey());
        self::assertSame($package, $event->getPackage());
        self::assertSame($container, $event->getContainer());
        self::assertSame($emitter, $event->getEmitter());

        self::assertFalse($event->hasStorageEntry(__CLASS__));

        $resultData = ['foo' , 'bar'];
        $event->addStorageEntry(__CLASS__, $resultData);

        self::assertTrue($event->hasStorageEntry(__CLASS__));
        self::assertSame(__CLASS__, $event->getStorageEntry(__CLASS__)->getIdentifier());
        self::assertSame($resultData, $event->getStorageEntry(__CLASS__)->getResult());

        $event->removeStorageEntry(__CLASS__);
        self::assertFalse($event->hasStorageEntry(__CLASS__));
    }

    #[Test]
    public function coreListenersAddStorageEntries(): void
    {
        /** @var PackageInitializationEvent $event */
        $event = $this->getContainer()->get(EventDispatcherInterface::class)->dispatch(
            new PackageInitializationEvent(
                'test_package_initialization',
                $this->getContainer()->get(PackageManager::class)->getPackage('test_package_initialization'),
            )
        );

        self::assertTrue($event->hasStorageEntry(ImportExtensionDataOnPackageInitialization::class));
        self::assertStringEndsWith(
            '/fileadmin/test_package_initialization',
            $event->getStorageEntry(ImportExtensionDataOnPackageInitialization::class)->getResult()
        );

        self::assertTrue($event->hasStorageEntry(ImportStaticSqlDataOnPackageInitialization::class));
        self::assertStringEndsWith(
            '/typo3conf/ext/test_package_initialization/ext_tables_static+adt.sql',
            $event->getStorageEntry(ImportStaticSqlDataOnPackageInitialization::class)->getResult()
        );

        self::assertTrue($event->hasStorageEntry(CheckForImportRequirements::class));
        $importRequirementsResult = $event->getStorageEntry(CheckForImportRequirements::class)->getResult();
        self::assertCount(1, $importRequirementsResult['importFiles']);
        self::assertStringEndsWith('data.xml', reset($importRequirementsResult['importFiles']));
        self::assertTrue($importRequirementsResult['siteInitialisationDirectoryExists']);
    }

    #[Test]
    public function customListenersAreCalled(): void
    {
        $packageInitializationEvent = null;
        $listenerResult = ['foo' => 'bar'];

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'package-initialization-listener',
            static function (PackageInitializationEvent $event) use (&$packageInitializationEvent, $listenerResult) {
                $packageInitializationEvent = $event;
                $event->addStorageEntry('package-initialization-listener', $listenerResult);
            }
        );

        $listenerProdiver = $container->get(ListenerProvider::class);
        $listenerProdiver->addListener(PackageInitializationEvent::class, 'package-initialization-listener');

        /** @var PackageActivationService $packageActivationService */
        $packageActivationService = $container->get(PackageActivationService::class);
        $packageActivationService->reloadExtensionData(['test_package_initialization']);

        self::assertInstanceOf(PackageInitializationEvent::class, $packageInitializationEvent);
        self::assertSame($listenerResult, $packageInitializationEvent->getStorageEntry('package-initialization-listener')->getResult());
    }
}
