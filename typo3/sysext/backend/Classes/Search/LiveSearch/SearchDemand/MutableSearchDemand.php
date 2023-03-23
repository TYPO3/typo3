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

namespace TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand;

/**
 * @internal for internal use only, no public API
 */
final class MutableSearchDemand extends SearchDemand
{
    public static function fromSearchDemand(SearchDemand $searchDemand): self
    {
        return new self($searchDemand->getProperties());
    }

    public function setProperty(DemandPropertyName $name, mixed $value): self
    {
        $this->demandProperties[$name->name] = new DemandProperty($name, $value);

        return $this;
    }

    public function freeze(): SearchDemand
    {
        return new SearchDemand($this->demandProperties);
    }
}
