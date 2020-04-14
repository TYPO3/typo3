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

namespace TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\FormElement;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\AbstractExtractor;

/**
 * @internal
 */
class MultiValuePropertiesExtractor extends AbstractExtractor
{

    /**
     * @param string $_
     * @param mixed $value
     * @param array $matches
     */
    public function __invoke(string $_, $value, array $matches)
    {
        [, $formElementType, $formEditorIndex] = $matches;

        if (
            $value !== 'Inspector-PropertyGridEditor'
            && $value !== 'Inspector-MultiSelectEditor'
            && $value !== 'Inspector-ValidationErrorMessageEditor'
            && $value !== 'Inspector-RequiredValidatorEditor'
        ) {
            return;
        }

        if ($value === 'Inspector-RequiredValidatorEditor') {
            $propertyPath = implode(
                '.',
                [
                    'formElementsDefinition',
                    $formElementType,
                    'formEditor',
                    'editors',
                    $formEditorIndex,
                    'configurationOptions',
                    'validationErrorMessage',
                    'propertyPath',
                ]
            );
        } else {
            $propertyPath = implode(
                '.',
                [
                    'formElementsDefinition',
                    $formElementType,
                    'formEditor',
                    'editors',
                    $formEditorIndex,
                    'propertyPath',
                ]
            );
        }

        $result = $this->extractorDto->getResult();

        if (ArrayUtility::isValidPath($this->extractorDto->getPrototypeConfiguration(), $propertyPath, '.')) {
            $result['formElements'][$formElementType]['multiValueProperties'][] = ArrayUtility::getValueByPath(
                $this->extractorDto->getPrototypeConfiguration(),
                $propertyPath,
                '.'
            );
        }

        if ($value === 'Inspector-PropertyGridEditor') {
            $result['formElements'][$formElementType]['multiValueProperties'][] = 'defaultValue';
        }

        $this->extractorDto->setResult($result);
    }
}
