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

namespace TYPO3\CMS\Reactions\Reaction;

/**
 * Why a reaction did not write a field of the record it creates.
 *
 * @internal This is a specific reaction implementation and is not considered part of the Public TYPO3 API.
 */
enum SkippedFieldReason: string
{
    /**
     * The key is no field of the target table the field map offers: left over from another
     * table the reaction pointed at before, or edited into the record by hand.
     */
    case NOT_MAPPABLE = 'notMappable';

    /**
     * The user the reaction impersonates may not write the field: it declares "exclude"
     * and the permission for it is not granted, or it is shown to admins only.
     */
    case NOT_PERMITTED = 'notPermitted';

    /**
     * The payload carries no value under the path the placeholder names, or none that can
     * be written into a field.
     */
    case PLACEHOLDER_UNRESOLVED = 'placeholderUnresolved';

    /**
     * The payload carries the path but nothing under it. Writing the empty value would
     * overrule the default the target table declares.
     */
    case RESOLVED_TO_NOTHING = 'resolvedToNothing';

    /**
     * A checkbox can hold neither a wording it understands nor a number of the range its
     * bitmask spans.
     */
    case VALUE_NOT_HOLDABLE = 'valueNotHoldable';

    /**
     * A select or radio does not offer the value as one of its items.
     */
    case VALUE_NOT_OFFERED = 'valueNotOffered';
}
