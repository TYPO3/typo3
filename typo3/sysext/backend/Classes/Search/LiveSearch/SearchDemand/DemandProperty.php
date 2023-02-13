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

final class DemandProperty
{
    public function __construct(
        private readonly DemandPropertyName $name,
        private readonly mixed $value,
    ) {
    }

    public function getName(): DemandPropertyName
    {
        return $this->name;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
