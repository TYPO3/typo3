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

namespace TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters;

use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\FormEngineDefinition;

/**
 * @internal
 */
class FlexFormFinisherOverridesConverterDto
{
    /**
        * @var FormEngineDefinition|null
     */
    protected ?FormEngineDefinition $formEngineDefinition = null;

    /**
     * @var array
     */
    protected $finisherDefinition = [];

    /**
     * @var string
     */
    protected $finisherIdentifier = '';

    /**
     * @var array
     */
    protected $flexFormSheetSettings = [];

    public function __construct(
        ?FormEngineDefinition $formEngineDefinition,
        array $finisherDefinition,
        string $finisherIdentifier,
        array $flexFormSheetSettings
    ) {
        $this->formEngineDefinition = $formEngineDefinition;
        $this->finisherDefinition = $finisherDefinition;
        $this->finisherIdentifier = $finisherIdentifier;
        $this->flexFormSheetSettings = $flexFormSheetSettings;
    }

    public function getFormEngineDefinition(): ?FormEngineDefinition
    {
        return $this->formEngineDefinition;
    }

    public function getFinisherDefinition(): array
    {
        return $this->finisherDefinition;
    }

    public function setFinisherDefinition(array $finisherDefinition): FlexFormFinisherOverridesConverterDto
    {
        $this->finisherDefinition = $finisherDefinition;

        return $this;
    }

    public function getFinisherIdentifier(): string
    {
        return $this->finisherIdentifier;
    }

    public function getFlexFormSheetSettings(): array
    {
        return $this->flexFormSheetSettings;
    }
}
