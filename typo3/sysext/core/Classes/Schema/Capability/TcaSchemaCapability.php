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

namespace TYPO3\CMS\Core\Schema\Capability;

/**
 * Contains all capabilities that can be defined in TCA
 * and are understandable by the Schema API.
 */
enum TcaSchemaCapability
{
    private const SYSTEM_CAPABILITIES = [
        self::CreatedAt,
        self::UpdatedAt,
        self::RestrictionStartTime,
        self::RestrictionEndTime,
        self::SoftDelete,
        self::EditLock,
        self::RestrictionDisabledField,
        self::InternalDescription,
        self::SortByField,
        self::RestrictionUserGroup,
    ];

    // TCA[ctrl][delete]
    case SoftDelete;

    // TCA[ctrl][crdate]
    case CreatedAt;

    // TCA[ctrl][tstamp]
    case UpdatedAt;

    // TCA[ctrl][sortby]
    case SortByField;

    // TCA[ctrl][default_sortby]
    case DefaultSorting;

    // TCA[ctrl][origUid]
    case AncestorReferenceField;

    // TCA[ctrl][editlock]
    case EditLock;

    // TCA[ctrl][descriptionColumn]
    case InternalDescription;

    // TCA[ctrl][language]
    case Language;

    // TCA[ctrl][workspace]
    case Workspace;

    // TCA[ctrl][label],TCA[ctrl][label_alt],TCA[ctrl][label_alt_force]...
    case Label;

    // TCA[ctrl][adminOnly]
    case AccessAdminOnly;

    // TCA[ctrl][readOnly]
    case AccessReadOnly;

    // TCA[ctrl][hideAtCopy]
    case HideRecordsAtCopy;

    // TCA[ctrl][hideTable]
    case HideInUi;

    // TCA[ctrl][prependAtCopy]
    case PrependLabelTextAtCopy;

    // TCA[ctrl][enablecolumns][disabled]
    case RestrictionDisabledField;

    // TCA[ctrl][enablecolumns][starttime]
    case RestrictionStartTime;

    // TCA[ctrl][enablecolumns][endtime]
    case RestrictionEndTime;

    // TCA[ctrl][enablecolumns][fe_group]
    case RestrictionUserGroup;

    case RestrictionRootLevel;

    // TCA[ctrl][ignoreWebMountRestriction] inverted
    case RestrictionWebMount;

    public static function getSystemCapabilities(): array
    {
        return self::SYSTEM_CAPABILITIES;
    }
}
