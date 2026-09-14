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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;

/**
 * Validation class for fields to be allowed for record creation in the "create record" reaction.
 *
 * The rule decides both which fields the form offers and which ones the reaction writes.
 *
 * @internal
 */
final class CreateRecordReactionField
{
    private const SUPPORTED_TYPES = ['input', 'text', 'email', 'number', 'datetime', 'color', 'link', 'check', 'radio', 'select'];

    /**
     * Of those, the types whose value is constrained to a set of items the integrator has
     * to know when it is typed into a text box. The field map renders the control of the
     * target table for these, so the value can be picked instead.
     */
    private const TYPES_WITH_ITEMS = ['check', 'radio', 'select'];

    /**
     * A checkbox is the one of them holding a bitmask rather than one of its items, so its
     * value is checked against the range that mask spans instead of against the list.
     */
    private const TYPE_HOLDING_A_BITMASK = 'check';

    public static function isAllowedForCreation(TcaSchema $schema, string $field): bool
    {
        return $schema->hasField($field)
            && self::isMappable($schema->getField($field));
    }

    public static function isMappable(FieldTypeInterface $fieldInfo): bool
    {
        // Relations are left out for now. A mapped value is checked against the items the
        // field declares before it is written, and a relation declares none: what is valid
        // is whichever rows the foreign table happens to hold, so a caller could name any
        // uid of it. An inline or file relation could not be mapped either way, being
        // written as child records rather than as a value. The check is on the resolved
        // field type, since a static select and one carrying a foreign_table both report
        // the type "select".
        if ($fieldInfo instanceof RelationalFieldTypeInterface) {
            return false;
        }
        // A select renders one control per renderType, and only selectSingle submits a
        // single value. The others submit a list, which is one control too many for a row
        // offering a fixed value beside a placeholder. A checkbox needs no such check: it
        // carries its whole bitmask in one hidden field.
        if ($fieldInfo->getType() === 'select'
            && ($fieldInfo->getConfiguration()['renderType'] ?? '') !== 'selectSingle'
        ) {
            return false;
        }
        // A column the target table declares read only offers no control to pick a value
        // with: the element of such a field renders its input disabled. What the field map
        // cannot offer, the reaction does not write either.
        if ($fieldInfo->getConfiguration()['readOnly'] ?? false) {
            return false;
        }
        return in_array($fieldInfo->getType(), self::SUPPORTED_TYPES, true);
    }

    /**
     * Whether a backend user may write the field, decided the way DataHandler decides it:
     * a field declaring "exclude" needs the permission for it to be granted, and one shown
     * to admins only is theirs alone.
     *
     * The same rule answers two questions with two different users. The field map offers
     * what the integrator editing the reaction may edit, and the reaction writes what the
     * user it impersonates may write, which is not the same user! DataHandler makes the
     * second call itself, but drops the field without logging anything, so a caller would
     * read the created record as carrying a value it never got.
     */
    public static function isWritableBy(
        FieldTypeInterface $fieldInfo,
        string $table,
        string $field,
        BackendUserAuthentication $backendUser
    ): bool {
        if (!$backendUser->isAdmin() && $fieldInfo->getDisplayConditions() === 'HIDE_FOR_NON_ADMINS') {
            return false;
        }
        return !$fieldInfo->supportsAccessControl()
            || $backendUser->check('non_exclude_fields', $table . ':' . $field);
    }

    public static function hasItems(string $type): bool
    {
        return in_array($type, self::TYPES_WITH_ITEMS, true);
    }

    public static function isComparedAgainstItems(string $type): bool
    {
        return self::hasItems($type) && !self::holdsBitmask($type);
    }

    public static function holdsBitmask(string $type): bool
    {
        return $type === self::TYPE_HOLDING_A_BITMASK;
    }
}
