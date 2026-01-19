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

/**
 * Sets renderingOptions.enabled to true for form elements if not explicitly configured.
 * Matches elements identified by their 'identifier' property.
 *
 * @internal
 */
class AddRenderableVisibilityConverter extends AbstractConverter
{
    /**
     * @param mixed $value
     */
    public function __invoke(string $key, $value): void
    {
        $formDefinition = $this->converterDto->getFormDefinition();

        // Remove the property name (type or identifier) to get the element path
        $pathParts = explode('.', $key);
        array_pop($pathParts);
        $elementPath = implode('.', $pathParts);

        $renderingOptionsPath = $elementPath !== '' ? $elementPath . '.renderingOptions.enabled' : 'renderingOptions.enabled';
        if (!ArrayUtility::isValidPath($formDefinition, $renderingOptionsPath, '.')) {
            $formDefinition = ArrayUtility::setValueByPath($formDefinition, $renderingOptionsPath, true, '.');
            $this->converterDto->setFormDefinition($formDefinition);
        }
    }
}
