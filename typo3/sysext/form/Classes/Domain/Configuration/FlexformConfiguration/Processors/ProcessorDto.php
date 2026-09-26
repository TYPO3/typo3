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

namespace TYPO3\CMS\Form\Domain\Configuration\FlexformConfiguration\Processors;

use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\FormEngineDefinition;

/**
 * Data container for finisher FlexForm processing
 *
 * @internal
 */
class ProcessorDto
{
    /**
     * @var string
     */
    protected $finisherIdentifier;

    /**
        * @var FormEngineDefinition
     */
    protected $formEngineDefinition;

    /**
     * @var array<string, mixed>
     */
    protected $finisherOptionsFromSetup;

    /**
     * @var array
     */
    protected $finisherDefinitionFromFormDefinition;

    /**
     * @var array
     */
    protected $result = [];

    public function __construct(
        string $finisherIdentifier,
        FormEngineDefinition $formEngineDefinition,
        array $finisherOptionsFromSetup,
        array $finisherDefinitionFromFormDefinition
    ) {
        $this->finisherIdentifier = $finisherIdentifier;
        $this->formEngineDefinition = $formEngineDefinition;
        $this->finisherOptionsFromSetup = $finisherOptionsFromSetup;
        $this->finisherDefinitionFromFormDefinition = $finisherDefinitionFromFormDefinition;
    }

    public function getFinisherIdentifier(): string
    {
        return $this->finisherIdentifier;
    }

    public function getFormEngineDefinition(): FormEngineDefinition
    {
        return $this->formEngineDefinition;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFinisherOptionsFromSetup(): array
    {
        return $this->finisherOptionsFromSetup;
    }

    public function getFinisherDefinitionFromFormDefinition(): array
    {
        return $this->finisherDefinitionFromFormDefinition;
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function setResult(array $result): ProcessorDto
    {
        $this->result = $result;

        return $this;
    }
}
