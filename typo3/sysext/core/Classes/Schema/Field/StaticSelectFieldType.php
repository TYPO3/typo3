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

namespace TYPO3\CMS\Core\Schema\Field;

use TYPO3\CMS\Core\Schema\Struct\SelectItem;

/**
 * This is a select type without any MM or foreign table logic.
 *
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
final readonly class StaticSelectFieldType extends AbstractFieldType
{
    public function getType(): string
    {
        return 'select';
    }

    public function isSearchable(): false
    {
        return false;
    }

    /**
     * @return SelectItem[]
     */
    public function getItems(): array
    {
        return is_array($this->configuration['items'] ?? false) ? array_map(
            static fn($item): SelectItem => SelectItem::fromTcaItemArray($item),
            $this->configuration['items']
        ) : [];
    }
}
