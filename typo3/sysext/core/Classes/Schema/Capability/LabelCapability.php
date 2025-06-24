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
 * Contains all information of compiling the label information of a schema.
 */
final readonly class LabelCapability implements SchemaCapabilityInterface
{
    public function __construct(
        protected ?string $primaryFieldName,
        /** @var string[] */
        protected array $additionalFieldNames,
        protected bool $alwaysRenderAdditionalFields,
        protected array $configuration,
    ) {}

    public function getPrimaryFieldName(): ?string
    {
        return $this->primaryFieldName;
    }

    public function hasPrimaryField(): bool
    {
        return $this->primaryFieldName !== null;
    }

    /**
     * @return string[]
     */
    public function getAdditionalFieldNames(): array
    {
        return $this->additionalFieldNames;
    }

    public function getAllLabelFieldNames(): array
    {
        return array_unique(array_filter(array_merge([$this->primaryFieldName], $this->additionalFieldNames)));
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
