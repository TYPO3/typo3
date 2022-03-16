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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;

/**
 * Apply FlexForm finisher option overrides
 *
 * @internal
 */
class FinisherOptionsFlexFormOverridesConverter
{
    /**
     * @var FlexFormFinisherOverridesConverterDto
     */
    protected $converterDto;

    /**
     * @param FlexFormFinisherOverridesConverterDto $converterDto
     */
    public function __construct(FlexFormFinisherOverridesConverterDto $converterDto)
    {
        $this->converterDto = $converterDto;
    }

    /**
     * Used for overriding finisher options with flexform settings
     * Flexform settings "win": When a setting is set in the form
     * definition and in flexform the one in flexform will overwrite the
     * one defined in the form definition.
     *
     * Here we adjust the parsed configuration and apply the overrides.
     *
     * @param string $_ unused in this context
     * @param mixed $__ unused in this context
     * @param array $matches the expression matches from the ArrayProcessor - for example matches of ^(.*)\.config\.type$
     */
    public function __invoke(string $_, $__, array $matches): void
    {
        [, $optionKey] = $matches;
        $prototypeFinisherDefinition = $this->converterDto->getPrototypeFinisherDefinition();
        $finisherDefinition = $this->converterDto->getFinisherDefinition();
        $finisherIdentifier = $this->converterDto->getFinisherIdentifier();
        $flexFormSheetSettings = $this->converterDto->getFlexFormSheetSettings();

        try {
            $value = ArrayUtility::getValueByPath(
                $flexFormSheetSettings['finishers'][$finisherIdentifier],
                $optionKey,
                '.'
            );
        } catch (MissingArrayPathException $exception) {
            return;
        }

        $fieldConfiguration = $prototypeFinisherDefinition['FormEngine']['elements'][$optionKey] ?? [];

        if ($fieldConfiguration['section'] ?? false) {
            $processedOptionValue = [];

            foreach ($value ?: [] as $optionListValue) {
                $key = $optionListValue[$fieldConfiguration['sectionItemKey']];
                $value = $optionListValue[$fieldConfiguration['sectionItemValue']];
                $processedOptionValue[$key] = $value;
            }

            if (!empty($processedOptionValue)) {
                $value = $processedOptionValue;
            }
        }

        $finisherDefinition = ArrayUtility::setValueByPath($finisherDefinition, 'options.' . $optionKey, $value, '.');

        $this->converterDto->setFinisherDefinition($finisherDefinition);
    }
}
