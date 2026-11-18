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

namespace TYPO3\CMS\Core\Security\Authorization\Subject;

use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;

/**
 * A field of a table as subject of an authorization check, as the field
 * object of the schema does not know its table:
 * isGranted(Scope::WRITE, SchemaField::fromSchema($schema, 'header')).
 * A field of a sub-schema, e.g. "tt_content.textmedia", is denied by
 * TYPO3 Core: check the field of the main schema.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
final readonly class SchemaField
{
    private function __construct(
        public TcaSchema $schema,
        public FieldTypeInterface $field,
    ) {}

    public static function fromSchema(TcaSchema $schema, string $fieldName): self
    {
        return new self($schema, $schema->getField($fieldName));
    }
}
