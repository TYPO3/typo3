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

namespace TYPO3\CMS\Extbase\Tests\Unit\DomainObject;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\CleanStateTracker;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractEntityTest extends UnitTestCase
{
    private CleanStateTracker $cleanStateTracker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanStateTracker = new CleanStateTracker();
    }

    #[Test]
    public function objectIsNotDirtyAfterSettingOriginalEntityDataWithSimpleProperties(): void
    {
        $domainObject = new class extends AbstractEntity {
            public $foo = 'Test';
            public $bar = 'It is raining outside';
        };
        $this->cleanStateTracker->setOriginalEntityData($domainObject);

        self::assertFalse($this->cleanStateTracker->isEntityDirty($domainObject));
    }

    #[Test]
    public function objectIsDirtyAfterSettingOriginalEntityDataWithSimplePropertiesAndModifyingThePropertiesAfterwards(): void
    {
        $domainObject = new class extends AbstractEntity {
            public $foo = 'Test';
            public $bar = 'It is raining outside';
        };
        $this->cleanStateTracker->setOriginalEntityData($domainObject);
        $domainObject->bar = 'Now it is sunny.';

        self::assertTrue($this->cleanStateTracker->isEntityDirty($domainObject));
    }

    #[Test]
    public function objectIsNotDirtyAfterSettingOriginalEntityDataWithObjectProperties(): void
    {
        $domainObject = new class extends AbstractEntity {
            public $foo;
            public $bar = 'It is raining outside';
        };
        $domainObject->foo = new \DateTime();
        $this->cleanStateTracker->setOriginalEntityData($domainObject);

        self::assertFalse($this->cleanStateTracker->isEntityDirty($domainObject));
    }

    #[Test]
    public function objectIsNotDirtyAfterSettingOriginalEntityDataWithOtherDomainObjectsAsProperties(): void
    {
        $domainObject = new class extends AbstractEntity {
            public $foo;
            public $bar;
        };

        $secondDomainObject = new class extends AbstractEntity {
            public $foo;
            public $bar;
        };

        $this->cleanStateTracker->setOriginalEntityData($secondDomainObject);
        $domainObject->foo = $secondDomainObject;
        $domainObject->bar = 'It is raining outside';
        $this->cleanStateTracker->setOriginalEntityData($domainObject);

        self::assertFalse($this->cleanStateTracker->isEntityDirty($domainObject));
    }

    /**
     * A payload serialized before the clean state moved into the tracker (for example the state of an open
     * multi-step form) contains the former private properties. They are dropped, the entity keeps no
     * originals and gets no dynamic property.
     */
    #[Test]
    public function unserializeDropsFormerCleanStatePropertiesOfAbstractDomainObject(): void
    {
        // Given: an entity and a payload with the former private properties _cleanProperties and _isClone
        $domainObject = new class extends AbstractEntity {
            public $foo = '';
        };
        $payload = [
            "\0" . AbstractDomainObject::class . "\0_cleanProperties" => ['foo' => 'Original'],
            "\0" . AbstractDomainObject::class . "\0_isClone" => true,
            "\0*\0uid" => 5,
            'foo' => 'Current',
        ];

        // When: the payload is unserialized
        $domainObject->__unserialize($payload);

        // Then: the values are restored, the former properties are dropped and the entity has no originals
        self::assertSame(5, $domainObject->getUid());
        self::assertSame('Current', $domainObject->foo);
        self::assertArrayNotHasKey('_cleanProperties', get_object_vars($domainObject));
        self::assertArrayNotHasKey('_isClone', get_object_vars($domainObject));
        self::assertSame([], $this->cleanStateTracker->getOriginalEntityData($domainObject));
    }
}
