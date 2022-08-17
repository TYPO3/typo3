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

/**
 * Contains all information of compiling the label information of a schema.
 *
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
final readonly class LabelCapability implements SchemaCapabilityInterface
{
    public function __construct(
        protected ?FieldTypeInterface $primaryField,
        /** @var FieldTypeInterface[] */
        protected array $additionalFields,
        protected bool $alwaysRenderAdditionalFields,
        protected array $configuration,
    ) {}

    public function getPrimaryField(): ?FieldTypeInterface
    {
        return $this->primaryField;
    }

    public function hasPrimaryField(): bool
    {
        return $this->primaryField !== null;
    }

    /**
     * @return array<FieldTypeInterface>
     */
    public function getAdditionalFields(): array
    {
        return $this->additionalFields;
    }

    public function alwaysRenderAdditionalFields(): bool
    {
        return $this->alwaysRenderAdditionalFields;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
