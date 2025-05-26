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

use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Validation class for tables to be allowed for record creation in the "create record" reaction
 *
 * @internal
 */
final readonly class CreateRecordReactionTable
{
    public function __construct(private string $table) {}

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
        if ($this->table === '') {
            return false;
        }
        $schemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        if (!$schemaFactory->has($this->table)) {
            return false;
        }
        return !$schemaFactory->get($this->table)->hasCapability(TcaSchemaCapability::AccessAdminOnly);
    }

    private function isInSelectItems(): bool
    {
        $schema = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('sys_reaction');
        if (!$schema->hasField('table_name')) {
            return false;
        }
        $fieldInformation = $schema->getField('table_name');

        return is_array($fieldInformation->getConfiguration()['items'])
            && in_array(
                $this->table,
                array_filter(array_column($fieldInformation->getConfiguration()['items'], 'value')),
                true
            );
    }
}
