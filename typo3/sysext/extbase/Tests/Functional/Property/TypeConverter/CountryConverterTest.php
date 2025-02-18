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

namespace TYPO3\CMS\Extbase\Tests\Functional\Property\TypeConverter;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Country\Country;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CountryConverterTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function convertToObject(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        $model = new class () extends AbstractEntity {
            protected ?Country $country = null;

            public function setCountry(Country $country): void
            {
                $this->country = $country;
            }

            public function getCountry(): ?Country
            {
                return $this->country;
            }
        };

        /** @var DomainObjectInterface $object */
        $object = $propertyMapper->convert(['country' => 'AT'], get_class($model));

        self::assertInstanceOf(get_class($model), $object);
        self::assertInstanceOf(Country::class, $object->_getProperty('country'));
        self::assertInstanceOf(Country::class, $object->getCountry());

        self::assertSame('AT', $object->_getProperty('country')->getAlpha2IsoCode());
        self::assertSame('AT', $object->getCountry()->getAlpha2IsoCode());
    }
}
