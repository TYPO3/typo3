<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\PropertyCollectionElement;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\AbstractExtractor;

/**
 * @internal
 */
class IsCreatablePropertyCollectionElementExtractor extends AbstractExtractor
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
            $value !== 'Inspector-FinishersEditor'
            && $value !== 'Inspector-ValidatorsEditor'
            && $value !== 'Inspector-RequiredValidatorEditor'
        ) {
            return;
        }

        $propertyCollectionName = $value === 'Inspector-FinishersEditor' ? 'finishers' : 'validators';

        $result = $this->extractorDto->getResult();

        if (
            $value === 'Inspector-FinishersEditor'
            || $value === 'Inspector-ValidatorsEditor'
        ) {
            $selectOptionsPath = implode(
                '.',
                [
                    'formElementsDefinition',
                    $formElementType,
                    'formEditor',
                    'editors',
                    $formEditorIndex,
                    'selectOptions',
                ]
            );
            if (!ArrayUtility::isValidPath($this->extractorDto->getPrototypeConfiguration(), $selectOptionsPath, '.')) {
                return;
            }
            $selectOptions = ArrayUtility::getValueByPath(
                $this->extractorDto->getPrototypeConfiguration(),
                $selectOptionsPath,
                '.'
            );
            foreach ($selectOptions as $selectOption) {
                $validatorIdentifier = $selectOption['value'] ?? '';
                if (empty($validatorIdentifier)) {
                    continue;
                }

                $result['formElements'][$formElementType]['collections'][$propertyCollectionName][$validatorIdentifier]['creatable'] = true;
            }
        } else {
            $validatorIdentifierPath = implode(
                '.',
                [
                    'formElementsDefinition',
                    $formElementType,
                    'formEditor',
                    'editors',
                    $formEditorIndex,
                    'validatorIdentifier',
                ]
            );
            if (!ArrayUtility::isValidPath($this->extractorDto->getPrototypeConfiguration(), $validatorIdentifierPath, '.')) {
                return;
            }
            $validatorIdentifier = ArrayUtility::getValueByPath(
                $this->extractorDto->getPrototypeConfiguration(),
                $validatorIdentifierPath,
                '.'
            );
            $result['formElements'][$formElementType]['collections'][$propertyCollectionName][$validatorIdentifier]['creatable'] = true;
        }

        $this->extractorDto->setResult($result);
    }
}
