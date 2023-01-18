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

namespace TYPO3\CMS\Core\Country;

/**
 * DTO that keeps the information about a country. Never instantiate directly,
 * use CountryProvider instead.
 */
class Country
{
    protected const LABEL_FILE = 'EXT:core/Resources/Private/Language/Iso/countries.xlf';

    public function __construct(
        protected string $alpha2,
        protected string $alpha3,
        protected string $name,
        protected string $numeric,
        protected string $flag,
        protected ?string $officialName
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function getLocalizedNameLabel(): string
    {
        return 'LLL:' . self::LABEL_FILE . ':' . $this->alpha2 . '.name';
    }

    public function getOfficialName(): ?string
    {
        return $this->officialName;
    }

    public function getLocalizedOfficialNameLabel(): string
    {
        return 'LLL:' . self::LABEL_FILE . ':' . $this->alpha2 . '.official_name';
    }

    public function getAlpha2IsoCode(): string
    {
        return $this->alpha2;
    }

    public function getAlpha3IsoCode(): string
    {
        return $this->alpha3;
    }

    public function getNumericRepresentation(): string
    {
        return $this->numeric;
    }

    public function getFlag(): string
    {
        return $this->flag;
    }
}
