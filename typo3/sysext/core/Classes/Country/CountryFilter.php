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
 * Filter object to limit countries to a subset of all countries.
 */
final class CountryFilter
{
    /**
     * @param string[] $excludeCountries
     * @param string[] $onlyCountries
     */
    public function __construct(protected array $excludeCountries = [], protected array $onlyCountries = [])
    {
    }

    /**
     * @return string[]
     */
    public function getExcludeCountries(): array
    {
        return array_map('strtoupper', $this->excludeCountries);
    }

    /**
     * @param string[] $excludeCountries
     * @return $this
     */
    public function setExcludeCountries(array $excludeCountries): CountryFilter
    {
        $this->excludeCountries = $excludeCountries;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getOnlyCountries(): array
    {
        return array_map('strtoupper', $this->onlyCountries);
    }

    /**
     * @param string[] $onlyCountries
     * @return $this
     */
    public function setOnlyCountries(array $onlyCountries): CountryFilter
    {
        $this->onlyCountries = $onlyCountries;
        return $this;
    }
}
