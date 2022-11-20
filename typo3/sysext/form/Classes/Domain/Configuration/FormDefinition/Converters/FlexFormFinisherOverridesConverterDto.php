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

/**
 * @internal
 */
class FlexFormFinisherOverridesConverterDto
{
    /**
     * @var array
     */
    protected $prototypeFinisherDefinition = [];

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
        array $prototypeFinisherDefinition,
        array $finisherDefinition,
        string $finisherIdentifier,
        array $flexFormSheetSettings
    ) {
        $this->prototypeFinisherDefinition = $prototypeFinisherDefinition;
        $this->finisherDefinition = $finisherDefinition;
        $this->finisherIdentifier = $finisherIdentifier;
        $this->flexFormSheetSettings = $flexFormSheetSettings;
    }

    public function getPrototypeFinisherDefinition(): array
    {
        return $this->prototypeFinisherDefinition;
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
