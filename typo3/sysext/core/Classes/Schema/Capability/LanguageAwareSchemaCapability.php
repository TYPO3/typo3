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

use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\LanguageFieldType;

/**
 * Contains all information if a schema is language-aware, meaning
 * it has a "languageField", a "translationOrigPointerField", maybe a "translationSourceField"
 * and maybe a "diffSourceField".
 *
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
final readonly class LanguageAwareSchemaCapability implements SchemaCapabilityInterface
{
    public function __construct(
        protected LanguageFieldType $languageField,
        protected FieldTypeInterface $originPointerField,
        protected ?FieldTypeInterface $translationSourceField,
        protected ?FieldTypeInterface $diffSourceField
    ) {}

    public function getLanguageField(): LanguageFieldType
    {
        // $this->languageField->getName() typically resolves to 'sys_language_uid'
        return $this->languageField;
    }

    public function getTranslationOriginPointerField(): FieldTypeInterface
    {
        return $this->originPointerField;
    }

    public function hasTranslationSourceField(): bool
    {
        return $this->translationSourceField !== null;
    }

    public function getTranslationSourceField(): ?FieldTypeInterface
    {
        return $this->translationSourceField;
    }

    public function getDiffSourceField(): ?FieldTypeInterface
    {
        return $this->diffSourceField;
    }

    public function hasDiffSourceField(): bool
    {
        return $this->diffSourceField !== null;
    }
}
