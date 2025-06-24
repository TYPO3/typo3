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
 */
final readonly class LanguageAwareSchemaCapability implements SchemaCapabilityInterface
{
    public function __construct(
        protected LanguageFieldType $languageField,
        protected FieldTypeInterface $originPointerField,
        protected ?FieldTypeInterface $translationSourceField,
        protected ?FieldTypeInterface $diffSourceField
    ) {}

    /**
     * languageField->getName() typically resolves to 'sys_language_uid'
     */
    public function getLanguageField(): LanguageFieldType
    {
        return $this->languageField;
    }

    /**
     * translationOriginPointerField->getName() typically resolves to 'l10n_parent' or 'l18n_parent'
     */
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

    /**
     * diffSourceField->getName() typically resolves to 'l10n_diffsource' or 'l18n_diffsource'
     */
    public function getDiffSourceField(): ?FieldTypeInterface
    {
        return $this->diffSourceField;
    }

    public function hasDiffSourceField(): bool
    {
        return $this->diffSourceField !== null;
    }
}
