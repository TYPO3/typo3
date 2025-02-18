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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Mapper;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Country\Country;
use TYPO3\CMS\Core\Country\CountryProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Person;
use TYPO3Tests\BlogExample\Domain\Repository\PersonRepository;

final class TcaTypeCountryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    #[Test]
    public function tcaTypeCountryCanBeHandled(): void
    {
        $countryProvider = $this->get(CountryProvider::class);
        $country = $countryProvider->getByAlpha2IsoCode('AT');

        $entity = new Person();
        $entity->setCountry($country);
        self::assertInstanceOf(Country::class, $entity->getCountry());

        $persistenceManager = $this->get(PersistenceManager::class);
        $persistenceManager->add($entity);
        $persistenceManager->persistAll();
        $uid = $persistenceManager->getIdentifierByObject($entity);
        $persistenceManager->clearState();

        $example = $persistenceManager->getObjectByIdentifier($uid, Person::class);

        self::assertEquals($example->getCountry(), $country);
    }

    #[Test]
    public function tcaTypeCountryCanRetrievedFromRepository(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TcaTypeCountryTestImport.csv');
        $personRepository = $this->get(PersonRepository::class);

        self::assertEquals(2, $personRepository->countAll());

        /** @var array<int, Person> $persons */
        $persons = $personRepository->findAll();
        self::assertCount(2, $persons);

        self::assertSame('AT', $persons[0]->getCountry()->getAlpha2IsoCode());
        self::assertSame('FR', $persons[1]->getCountry()->getAlpha2IsoCode());
    }

}
