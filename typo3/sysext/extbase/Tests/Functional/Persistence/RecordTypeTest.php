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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Page\PageParts;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Machine;
use TYPO3Tests\BlogExample\Domain\Model\Vehicle;
use TYPO3Tests\BlogExample\Domain\Repository\CarRepository;
use TYPO3Tests\BlogExample\Domain\Repository\MachineRepository;
use TYPO3Tests\BlogExample\Domain\Repository\VehicleRepository;

/**
 * Record types: the type field of a table (`ctrl.type`) and the record types of Extbase's class mapping, that is
 * a base class with subclasses that each have a record type.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (suffix "ByCore"),
 * once with Extbase (suffix "ByExtbase"). The core reads with the `select` of the `CONTENT` cObject, called through
 * `ContentObjectRenderer::getRecords()`, which does not filter by type, and writes with DataHandler as an
 * administrator. Both sides are compared by title. An Extbase test that differs from core is skipped.
 *
 * The vehicle table has the type field `type` with the types "0" and "car". The model Vehicle has no record type, the
 * subclass Car has the record type "car"; both are mapped to the vehicle table. The machine table has the type field
 * `type` with the types "machine", "robot" and "drone" and the column `battery`, which only the type "robot" shows in
 * the form. The model Machine has the record type "machine" and the subclasses Robot (record type "robot", the only
 * class with the property `battery`) and Drone (record type "drone"); all three are mapped to the machine table.
 * The storage page 2 holds the vehicles or machines of the fixture of the test (Fixtures/RecordType/Given/).
 */
final class RecordTypeTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordType/Given/Base.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $GLOBALS['EXEC_TIME'] = 1700000000;
        $this->get(Context::class)->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@1700000000')));
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => 2],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
    }

    /**
     * The core does not filter by type. The select of the `CONTENT` cObject for the storage page returns all three
     * vehicles in the order of their uids: "Untyped" with an empty `type`, "Typed zero" with the `type` "0" and "Sports
     * car" with the `type` "car".
     *
     * @see readingVehiclesWithAnyTypeThroughTheBaseClassByExtbase()
     */
    #[Test]
    public function readingVehiclesWithAnyTypeThroughTheBaseClassByCore(): void
    {
        // Given: three vehicles with the types empty, "0" and "car" exist on the storage page
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordType/Given/Vehicles.csv');

        // When: the vehicles of the storage page are read
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_vehicle', [
            'pidInList' => '2',
            'orderBy' => 'uid',
        ]);
        $titles = array_column($rows, 'title');

        // Then: all three are returned
        self::assertSame(['Untyped', 'Typed zero', 'Sports car'], $titles);
    }

    /**
     * Read through the repository of the model Vehicle, which has no record type, Extbase returns "Sports car" only.
     * Because the subclass Car with the record type "car" is configured, Extbase restricts the query to the types of
     * the subclasses, so "Untyped" with the empty `type` and "Typed zero" with the `type` "0" are not found. Without a
     * configured subclass the same repository would find all three.
     *
     * @see readingVehiclesWithAnyTypeThroughTheBaseClassByCore()
     */
    #[Test]
    public function readingVehiclesWithAnyTypeThroughTheBaseClassByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: once a subclass with a record type is configured, the repository of the base class finds only rows with the type of the subclass, not rows with an empty type or the type "0", see Forge #91661.');

        // Given: three vehicles with the types empty, "0" and "car" exist on the storage page
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordType/Given/Vehicles.csv');

        // When: the vehicles of the storage page are read through the repository of the base class
        $this->get(PersistenceManager::class)->clearState();
        $query = $this->get(VehicleRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $titles = array_map(static fn(Vehicle $record): string => $record->getTitle(), $query->execute()->toArray());

        // Then: all three are returned
        self::assertSame(['Untyped', 'Typed zero', 'Sports car'], $titles);
    }

    /**
     * Reading the vehicles with the `type` "car", the core selects the rows of the storage page whose `type` is "car"
     * and returns "Sports car" only.
     *
     * @see readingVehiclesOfTheSubclassTypeByExtbase()
     */
    #[Test]
    public function readingVehiclesOfTheSubclassTypeByCore(): void
    {
        // Given: three vehicles with the types empty, "0" and "car" exist on the storage page
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordType/Given/Vehicles.csv');

        // When: the vehicles of the type "car" are read
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_vehicle', [
            'pidInList' => '2',
            'where' => "type = 'car'",
            'orderBy' => 'uid',
        ]);
        $titles = array_column($rows, 'title');

        // Then: only the car is returned
        self::assertSame(['Sports car'], $titles);
    }

    /**
     * Read through the repository of the subclass Car, Extbase returns "Sports car" only, the one row with the record
     * type "car", as the core does when it selects by that type.
     *
     * @see readingVehiclesOfTheSubclassTypeByCore()
     */
    #[Test]
    public function readingVehiclesOfTheSubclassTypeByExtbase(): void
    {
        // Given: three vehicles with the types empty, "0" and "car" exist on the storage page
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordType/Given/Vehicles.csv');

        // When: the vehicles of the storage page are read through the repository of the subclass
        $this->get(PersistenceManager::class)->clearState();
        $query = $this->get(CarRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $titles = array_map(static fn(Vehicle $record): string => $record->getTitle(), $query->execute()->toArray());

        // Then: only the car is returned
        self::assertSame(['Sports car'], $titles);
    }

    /**
     * A vehicle "Created" is created through DataHandler without a value for the `type`. DataHandler stores the default
     * of the type field, "0", and the select of the `CONTENT` cObject for the storage page returns the vehicle, because
     * the core does not filter by type.
     *
     * @see aVehicleCreatedWithoutTypeIsFoundThroughTheBaseClassByExtbase()
     */
    #[Test]
    public function aVehicleCreatedWithoutTypeIsFoundThroughTheBaseClassByCore(): void
    {
        // Given: the storage page has no vehicles

        // When: a vehicle "Created" is created without a type
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_blogexample_domain_model_vehicle' => ['NEW1' => ['pid' => 2, 'title' => 'Created']],
        ], []);
        $dataHandler->process_datamap();
        self::assertSame([], $dataHandler->errorLog);

        // Then: the vehicle has the type "0" and is returned when the storage page is read
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RecordType/CreatedVehicle.csv');
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_vehicle', [
            'pidInList' => '2',
            'orderBy' => 'uid',
        ]);
        $titles = array_column($rows, 'title');
        self::assertSame(['Created'], $titles);
    }

    /**
     * A new object of the model Vehicle with the title "Created" is added to the repository and persisted. Extbase
     * stores the same row as DataHandler, with the `type` "0", but the repository of the model Vehicle does not find
     * the object afterwards: with the subclass Car configured it looks only for the type "car", so the result is empty.
     *
     * @see aVehicleCreatedWithoutTypeIsFoundThroughTheBaseClassByCore()
     */
    #[Test]
    public function aVehicleCreatedWithoutTypeIsFoundThroughTheBaseClassByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: an object of the base class is stored with the type "0" and then not found by the repository of the base class, because with a subclass configured the query only asks for the type of the subclass, see Forge #91661.');

        // Given: the storage page has no vehicles

        // When: an object of the base class is added and persisted
        // @phpstan-ignore deadCode.unreachable
        $vehicle = new Vehicle();
        $vehicle->setTitle('Created');
        $vehicle->setPid(2);
        $this->get(VehicleRepository::class)->add($vehicle);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the vehicle has the type "0" and is returned when the storage page is read
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/RecordType/CreatedVehicle.csv');
        $this->get(PersistenceManager::class)->clearState();
        $query = $this->get(VehicleRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $titles = array_map(static fn(Vehicle $record): string => $record->getTitle(), $query->execute()->toArray());
        self::assertSame(['Created'], $titles);
    }

    /**
     * The machines of the storage page are selected with the condition `"battery = 'lithium'"`. The core returns
     * "Lithium robot" only: the column `battery` is filled for the robots, the machine and the drone have it empty, and
     * the core does not look at the type.
     *
     * @see aConditionOnAFieldOfOneSubtypeFindsItsRowsByExtbase()
     */
    #[Test]
    public function aConditionOnAFieldOfOneSubtypeFindsItsRowsByCore(): void
    {
        // Given: a machine, two robots with the batteries "lithium" and "nickel" and a drone exist on the storage page
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordType/Given/Machines.csv');

        // When: the machines with the battery "lithium" are read
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_machine', [
            'pidInList' => '2',
            'where' => "battery = 'lithium'",
            'orderBy' => 'uid',
        ]);
        $titles = array_column($rows, 'title');

        // Then: only the robot with that battery is returned
        self::assertSame(['Lithium robot'], $titles);
    }

    /**
     * The repository of the base class Machine finds the machines with the constraint `equals("battery", "lithium")`,
     * although the property `battery` exists only in the subclass Robot. Extbase returns "Lithium robot" only, as an
     * object of the class Robot, like the core.
     *
     * @see aConditionOnAFieldOfOneSubtypeFindsItsRowsByCore()
     */
    #[Test]
    public function aConditionOnAFieldOfOneSubtypeFindsItsRowsByExtbase(): void
    {
        // Given: a machine, two robots with the batteries "lithium" and "nickel" and a drone exist on the storage page
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordType/Given/Machines.csv');

        // When: the machines with the battery "lithium" are read through the repository of the base class
        $this->get(PersistenceManager::class)->clearState();
        $query = $this->get(MachineRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('battery', 'lithium'));
        $titles = array_map(static fn(Machine $record): string => $record->getTitle(), $query->execute()->toArray());

        // Then: only the robot with that battery is returned
        self::assertSame(['Lithium robot'], $titles);
    }

    /**
     * The machines of the storage page are selected with the condition `"battery != 'lithium'"`. The core returns
     * "Plain machine", "Nickel robot" and "Drone" in the order of their uids: the rows of the types that do not have
     * the field have an empty `battery`, which differs from "lithium", so they are part of the result.
     *
     * @see aNegatedConditionOnAFieldOfOneSubtypeKeepsTheRowsOfTheOtherTypesByExtbase()
     */
    #[Test]
    public function aNegatedConditionOnAFieldOfOneSubtypeKeepsTheRowsOfTheOtherTypesByCore(): void
    {
        // Given: a machine, two robots with the batteries "lithium" and "nickel" and a drone exist on the storage page
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordType/Given/Machines.csv');

        // When: the machines whose battery is not "lithium" are read
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_machine', [
            'pidInList' => '2',
            'where' => "battery != 'lithium'",
            'orderBy' => 'uid',
        ]);
        $titles = array_column($rows, 'title');

        // Then: the machine and the drone with an empty battery and the other robot are returned
        self::assertSame(['Plain machine', 'Nickel robot', 'Drone'], $titles);
    }

    /**
     * The repository of the base class Machine finds the machines with the constraint `logicalNot(equals("battery",
     * "lithium"))`. Extbase returns "Plain machine", "Nickel robot" and "Drone", like the core, the rows with an empty
     * `battery` included.
     *
     * @see aNegatedConditionOnAFieldOfOneSubtypeKeepsTheRowsOfTheOtherTypesByCore()
     */
    #[Test]
    public function aNegatedConditionOnAFieldOfOneSubtypeKeepsTheRowsOfTheOtherTypesByExtbase(): void
    {
        // Given: a machine, two robots with the batteries "lithium" and "nickel" and a drone exist on the storage page
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordType/Given/Machines.csv');

        // When: the machines whose battery is not "lithium" are read through the repository of the base class
        $this->get(PersistenceManager::class)->clearState();
        $query = $this->get(MachineRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->logicalNot($query->equals('battery', 'lithium')));
        $titles = array_map(static fn(Machine $record): string => $record->getTitle(), $query->execute()->toArray());

        // Then: the machine and the drone with an empty battery and the other robot are returned
        self::assertSame(['Plain machine', 'Nickel robot', 'Drone'], $titles);
    }
}
