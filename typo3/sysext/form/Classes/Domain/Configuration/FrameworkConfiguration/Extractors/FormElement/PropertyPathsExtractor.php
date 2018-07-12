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
class PropertyPathsExtractor extends AbstractExtractor
{

    /**
     * @param string $_
     * @param mixed $value
     * @param array $matches
     */
    public function __invoke(string $_, $value, array $matches)
    {
        $formElementPropertyPaths = $this->getPropertyPaths($value, $matches);

        $result = $this->extractorDto->getResult();
        $result = array_merge_recursive($result, ['formElements' => $formElementPropertyPaths]);
        $this->extractorDto->setResult($result);
    }

    /**
     * @param string $value
     * @param array $matches
     * @return array
     */
    protected function getPropertyPaths(string $value, array $matches): array
    {
        [, $formElementType, $formEditorIndex] = $matches;

        $paths[$formElementType]['propertyPaths'] = [];
        $templateNamePath = implode(
            '.',
            [
                'formElementsDefinition',
                $formElementType,
                'formEditor',
                'editors',
                $formEditorIndex,
                'templateName',
            ]
        );
        $templateName = ArrayUtility::getValueByPath(
            $this->extractorDto->getPrototypeConfiguration(),
            $templateNamePath,
            '.'
        );

        // Special processing of "Inspector-GridColumnViewPortConfigurationEditor" inspector editors.
        // Expand the property path which contains a "{@viewPortIdentifier}" placeholder
        // to X property paths which contain all available placeholder replacements.
        if ($templateName === 'Inspector-GridColumnViewPortConfigurationEditor') {
            $viewPortsPath = implode(
                '.',
                [
                    'formElementsDefinition',
                    $formElementType,
                    'formEditor',
                    'editors',
                    $formEditorIndex,
                    'configurationOptions',
                    'viewPorts',
                ]
            );
            $viewPorts = ArrayUtility::getValueByPath($this->extractorDto->getPrototypeConfiguration(), $viewPortsPath, '.');
            foreach ($viewPorts as $viewPort) {
                $viewPortIdentifier = $viewPort['viewPortIdentifier'];
                $propertyPath = str_replace('{@viewPortIdentifier}', $viewPortIdentifier, $value);
                $paths[$formElementType]['propertyPaths'][] = $propertyPath;
            }
        } else {
            $paths[$formElementType]['propertyPaths'][] = $value;
        }
        return $paths;
    }
}
