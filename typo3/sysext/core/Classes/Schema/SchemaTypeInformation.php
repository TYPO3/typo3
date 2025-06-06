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
 * It is possible to use a DB field in TCA for referencing the actual type of record, by dividing the schema
 * in subschema by a type. For example, "pages" has a "type" field which references the "doktype" field
 * of the "pages" table. This is defined in TCA[ctrl][type] property.
 *
 * However, it is also possible to use a field of a foreign table to define the type of record -
 * for example - the "sys_file_reference" table has type "uid_foreign:title". The uid_foreign DB field
 * of "sys_file_reference" references the "uid" field of the "sys_file" table (as defined in the "uid_foreign" field
 * of "sys_file_reference", and the "title" field is then pointing to the related references' schema
 *
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
class SchemaTypeInformation
{
    public function __construct(
        public string $schemaName,
        public string $localFieldName,
        public ?string $foreignFieldName = null,
        public ?string $foreignSchemaName = null
    ) {}

    public function isForeignTableType(): bool
    {
        return $this->foreignFieldName !== null && $this->foreignSchemaName !== null;
    }
}
