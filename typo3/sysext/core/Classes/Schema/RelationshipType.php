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
    // A direct relation, e.g. sys_file.metadata => sys_file_metadata
    case OneToOne = '1:1';

    // A record with active relations, e.g. inline elements blog_article.comments => comment. The reference
    // to the left side is stored in a pointer field in the right side. Typically used when 'foreign_field' is set.
    case OneToMany = '1:n';

    // One item is selected on the active site, e.g. be_users.avatar => file, while file can be selected by any user
    case ManyToOne = 'n:1';

    // Regular MM intermediate table is used to store data
    case ManyToMany = 'mm';

    // An item list (separated by comma) is stored (like select type is doing)
    case List = 'list';

    // Type can not be defined
    case Undefined = '';

    public static function fromTcaConfiguration(array $configuration): self
    {
        if (isset($configuration['config'])) {
            $configuration = $configuration['config'];
        }
        if (isset($configuration['MM'])) {
            return self::ManyToMany;
        }
        if (isset($configuration['relationship'])) {
            return match ($configuration['relationship']) {
                'oneToOne' => self::OneToOne,
                'oneToMany' => self::OneToMany,
                'manyToOne' => self::ManyToOne,
                default => throw new \UnexpectedValueException('Invalid relationship type: ' . $configuration['relationship'], 1724661829),
            };
        }
        if (isset($configuration['foreign_field'])) {
            return self::OneToMany;
        }
        if (isset($configuration['foreign_table'])) {
            // ManyToOne (as with `renderType=selectSingle`) is
            // handled by `relationship` configuration above.
            // See `TcaPreparation::configureSelectSingle()`.
            return self::List;
        }
        if (($configuration['type'] ?? '') === 'group') {
            return self::List;
        }
        return self::Undefined;
    }

    public function isToOne(): bool
    {
        return in_array($this, [self::OneToOne, self::ManyToOne], true);
    }

    public function isSingularRelationship(): bool
    {
        return in_array($this, [self::OneToOne, self::ManyToOne, self::OneToMany, self::List], true);
    }
}
