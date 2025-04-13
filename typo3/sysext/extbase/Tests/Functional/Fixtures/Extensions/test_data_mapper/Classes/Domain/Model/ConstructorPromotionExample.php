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

namespace TYPO3Tests\TestDataMapper\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Demonstrates correct design of a extbase model using constructor property promotions,
 * expect CPP parameters explicitly checked for not initialized state in tests.
 */
final class ConstructorPromotionExample extends AbstractEntity
{
    // Note that constructor parameter promotion with default assignment handling is different
    // from defining them directly as class properties. That means, that for example following
    // CPP:
    //
    //  public function __construct(protected ?\DateTime $initializedDateTimeProperty = null) {}
    //
    // is not the replacement for the directly typed class property:
    //
    //  protected ?\DateTime $initializedDateTimeProperty = null;
    //
    // Using CPP, PHP defines the aforementioned CPP as following class property:
    //
    //  protected ?\DateTime $initializedDateTimeProperty;
    //
    // which is a not initialized property and honored with a fatal error when tried to read it before set it
    // at least to null as default value when the `__construct()` method is not used to instantiate the model
    // class. Extbase ORM omits the constructor by using the doctrine object instantiator package, defining
    // properties in not-initialized state, which is expected behaviour based on how PHP implemented `CPP`.
    //
    // That means, that to ensure all properties as initialized developer need add all the assignments of the
    // default values in the `initializeObject()` method again, which is called by Extbase ORM and ensures the
    // properties with assignments as initialized.
    //
    // That is important when a extbase model is hydrated from a dataset not holding ALL property in the array,
    // for example only retrieving a subset of the database table. That could happen if a model define property
    // for a new field and Database Analyzer has not been run yet or explicitly only selecting a narrowed down
    // field list of the table.
    //
    // The implementor of the model using CPP is responsible to ensure having all properties initialized in
    // direct code usage (constructor) and for hydration using `initializeObject()`. It is possible to make
    // `getter` save using `isset()` to check if the property is initialized and return the default assignment,
    // which would not help when using the object in a fluid template and the object accessor tries to get the
    // property directly instead of using a getter, because it is defined but not initialized.
    public function __construct(
        // Required (no default values)
        protected string $uninitializedStringProperty,
        protected ?\DateTime $uninitializedDateTimeProperty,
        protected \DateTime $uninitializedMandatoryDateTimeProperty,
        public $unknownType,
        public Enum\StringBackedEnum $stringBackedEnum,
        public Enum\IntegerBackedEnum $integerBackedEnum,

        // Optional (default values)
        protected string $firstProperty = '',
        protected int $secondProperty = 0,
        protected float $thirdProperty = 0.0,
        protected bool $fourthProperty = true,
        protected ?\DateTime $initializedDateTimeProperty = null,
        protected ?\DateTime $initializedDateTimePropertyDate = null,
        protected ?\DateTime $initializedDateTimePropertyDatetime = null,
        protected ?\DateTime $initializedDateTimePropertyTime = null,
        protected ?CustomDateTime $customDateTime = null,
        public ?Enum\StringBackedEnum $nullableStringBackedEnum = null,
        public ?Enum\IntegerBackedEnum $nullableIntegerBackedEnum = null
    ) {}

    /**
     * Ensure that all properties are initialized correctly when hydrated by Extbase ORM,
     * omitting to call {@see ConstructorPromotionExample::__construct()}. Required since
     * PHP does not work as many would expect at first, which means that the assignments
     * of the CPP parameters are only executed when the constructor is called, which is
     * different to having the property with a default assignment directly on class level.
     *
     * To mitigate this we need to redo the same assignments here.
     */
    public function initializeObject(): void
    {
        $this->firstProperty = '';
        $this->secondProperty = 0;
        $this->thirdProperty = 0.0;
        $this->fourthProperty = true;

        $this->initializedDateTimeProperty = null;
        $this->initializedDateTimePropertyDate = null;
        $this->initializedDateTimePropertyDatetime = null;
        $this->initializedDateTimePropertyTime = null;

        $this->customDateTime = null;
        $this->nullableStringBackedEnum = null;
        $this->nullableIntegerBackedEnum = null;
    }

    public function getFirstProperty(): string
    {
        return $this->firstProperty;
    }

    public function setFirstProperty(string $firstProperty): void
    {
        $this->firstProperty = $firstProperty;
    }

    public function getSecondProperty(): int
    {
        return $this->secondProperty;
    }

    public function setSecondProperty(int $secondProperty): void
    {
        $this->secondProperty = $secondProperty;
    }

    public function getThirdProperty(): float
    {
        return $this->thirdProperty;
    }

    public function setThirdProperty(float $thirdProperty): void
    {
        $this->thirdProperty = $thirdProperty;
    }

    public function isFourthProperty(): bool
    {
        return $this->fourthProperty;
    }

    public function setFourthProperty(bool $fourthProperty): void
    {
        $this->fourthProperty = $fourthProperty;
    }

    public function getUninitializedStringProperty(): string
    {
        return $this->uninitializedStringProperty;
    }

    public function setUninitializedStringProperty(string $uninitializedStringProperty): void
    {
        $this->uninitializedStringProperty = $uninitializedStringProperty;
    }

    public function getUninitializedDateTimeProperty(): ?\DateTime
    {
        return $this->uninitializedDateTimeProperty;
    }

    public function setUninitializedDateTimeProperty(?\DateTime $uninitializedDateTimeProperty): void
    {
        $this->uninitializedDateTimeProperty = $uninitializedDateTimeProperty;
    }

    public function getUninitializedMandatoryDateTimeProperty(): \DateTime
    {
        return $this->uninitializedMandatoryDateTimeProperty;
    }

    public function setUninitializedMandatoryDateTimeProperty(\DateTime $uninitializedMandatoryDateTimeProperty): void
    {
        $this->uninitializedMandatoryDateTimeProperty = $uninitializedMandatoryDateTimeProperty;
    }

    public function getInitializedDateTimeProperty(): ?\DateTime
    {
        return $this->initializedDateTimeProperty;
    }

    public function setInitializedDateTimeProperty(?\DateTime $initializedDateTimeProperty): void
    {
        $this->initializedDateTimeProperty = $initializedDateTimeProperty;
    }

    public function getInitializedDateTimePropertyDate(): ?\DateTime
    {
        return $this->initializedDateTimePropertyDate;
    }

    public function setInitializedDateTimePropertyDate(?\DateTime $initializedDateTimePropertyDate): void
    {
        $this->initializedDateTimePropertyDate = $initializedDateTimePropertyDate;
    }

    public function getInitializedDateTimePropertyDatetime(): ?\DateTime
    {
        return $this->initializedDateTimePropertyDatetime;
    }

    public function setInitializedDateTimePropertyDatetime(?\DateTime $initializedDateTimePropertyDatetime): void
    {
        $this->initializedDateTimePropertyDatetime = $initializedDateTimePropertyDatetime;
    }

    public function getInitializedDateTimePropertyTime(): ?\DateTime
    {
        return $this->initializedDateTimePropertyTime;
    }

    public function setInitializedDateTimePropertyTime(?\DateTime $initializedDateTimePropertyTime): void
    {
        $this->initializedDateTimePropertyTime = $initializedDateTimePropertyTime;
    }

    public function getCustomDateTime(): ?CustomDateTime
    {
        return $this->customDateTime;
    }

    public function setCustomDateTime(?CustomDateTime $customDateTime): void
    {
        $this->customDateTime = $customDateTime;
    }
}
