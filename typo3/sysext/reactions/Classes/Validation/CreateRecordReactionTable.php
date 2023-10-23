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

namespace TYPO3\CMS\Reactions\Validation;

use TYPO3\CMS\Core\Schema\Struct\SelectItem;

/**
 * Validation class for tables to be allowed for record creation in the "create record" reaction
 *
 * @internal
 */
final class CreateRecordReactionTable
{
    public function __construct(private readonly string $table) {}

    public static function fromSelectItem(SelectItem $selectItem): CreateRecordReactionTable
    {
        return new self((string)$selectItem->getValue());
    }

    public function isAllowedForCreation(): bool
    {
        return $this->isAllowedForItemsProcFunc()
            && $this->isInSelectItems();
    }

    public function isAllowedForItemsProcFunc(): bool
    {
        return $this->table !== ''
            && is_array($GLOBALS['TCA'][$this->table] ?? false)
            && !($GLOBALS['TCA'][$this->table]['ctrl']['adminOnly'] ?? false);
    }

    private function isInSelectItems(): bool
    {
        return is_array($GLOBALS['TCA']['sys_reaction']['columns']['table_name']['config']['items'] ?? false)
            && in_array(
                $this->table,
                array_filter(
                    array_column($GLOBALS['TCA']['sys_reaction']['columns']['table_name']['config']['items'], 'value')
                ),
                true
            );
    }
}
