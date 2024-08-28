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

class Example extends AbstractEntity
{
    protected string $firstProperty = '';
    protected int $secondProperty = 0;
    protected float $thirdProperty = 0.0;
    protected bool $fourthProperty = true;
    protected string $uninitializedStringProperty;
    protected ?\DateTime $uninitializedDateTimeProperty;
    protected \DateTime $uninitializedMandatoryDateTimeProperty;
    protected ?\DateTime $initializedDateTimeProperty = null;
    protected ?\DateTime $initializedDateTimePropertyDate = null;
    protected ?\DateTime $initializedDateTimePropertyDatetime = null;
    protected ?\DateTime $initializedDateTimePropertyTime = null;
    protected ?CustomDateTime $customDateTime = null;
    public $unknownType;
    public Enum\StringBackedEnum $stringBackedEnum;
    public ?Enum\StringBackedEnum $nullableStringBackedEnum = null;
    public Enum\IntegerBackedEnum $integerBackedEnum;
    public ?Enum\IntegerBackedEnum $nullableIntegerBackedEnum = null;

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
