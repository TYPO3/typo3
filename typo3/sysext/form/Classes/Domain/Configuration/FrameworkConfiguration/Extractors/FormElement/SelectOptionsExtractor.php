<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\FormElement;

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
class SelectOptionsExtractor extends AbstractExtractor
{

    /**
     * @param string $_
     * @param mixed $value
     * @param array $matches
     */
    public function __invoke(string $_, $value, array $matches)
    {
        [, $formElementType, $formEditorIndex] = $matches;

        $templateName = ArrayUtility::getValueByPath(
            $this->extractorDto->getPrototypeConfiguration(),
            implode(
                '.',
                [
                    'formElementsDefinition',
                    $formElementType,
                    'formEditor',
                    'editors',
                    $formEditorIndex,
                    'templateName',
                ]
            ),
            '.'
        );

        if ($templateName === 'Inspector-FinishersEditor') {
            $propertyPath = '_finishers';
        } elseif ($templateName === 'Inspector-ValidatorsEditor') {
            $propertyPath = '_validators';
        } else {
            if ($templateName === 'Inspector-RequiredValidatorEditor') {
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

            $propertyPath = ArrayUtility::getValueByPath(
                $this->extractorDto->getPrototypeConfiguration(),
                $propertyPath,
                '.'
            );
        }

        $result = $this->extractorDto->getResult();
        $result['formElements'][$formElementType]['selectOptions'][$propertyPath][] = $value;
        $this->extractorDto->setResult($result);
    }
}
