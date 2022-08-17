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

namespace TYPO3\CMS\Core\Schema;

/**
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
enum RelationshipType: string
{
    // The reference to the parent is stored in a pointer field in the child record
    // Typically used when 'foreign_field' is set
    case ForeignField = 'field';

    // Regular MM intermediate table is used to store data
    case ManyToMany = 'mm';

    // An item list (separated by comma) is stored (like select type is doing)
    case List = 'list';

    // Do we really need this?
    // @todo check if we need this
    case Static = 'static';

    case Undefined = '';

    public static function fromTcaConfiguration(array $configuration): self
    {
        if (isset($configuration['config'])) {
            $configuration = $configuration['config'];
        }
        if (isset($configuration['MM'])) {
            return self::ManyToMany;
        }
        if (isset($configuration['foreign_field'])) {
            return self::ForeignField;
        }
        if (isset($configuration['foreign_table'])) {
            return self::List;
        }
        if (($configuration['type'] ?? '') === 'group') {
            return self::List;
        }
        if (($configuration['type'] ?? '') === 'select') {
            return self::Static;
        }
        return self::Undefined;
    }
}
