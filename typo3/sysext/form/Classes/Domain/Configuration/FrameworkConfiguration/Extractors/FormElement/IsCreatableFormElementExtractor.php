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
class IsCreatableFormElementExtractor extends AbstractExtractor
{

    /**
     * @param string $_
     * @param mixed $value
     * @param array $matches
     */
    public function __invoke(string $_, $value, array $matches)
    {
        [, $formElementType] = $matches;

        $formElementGroup = $value;

        $result = $this->extractorDto->getResult();

        if (!ArrayUtility::isValidPath(
            $this->extractorDto->getPrototypeConfiguration(),
            'formElementsDefinition.' . $formElementType . '.formEditor.groupSorting',
            '.'
        )) {
            $result['formElements'][$formElementType]['creatable'] = false;
            $this->extractorDto->setResult($result);
            return;
        }

        $formElementGroups = array_keys(
            ArrayUtility::getValueByPath($this->extractorDto->getPrototypeConfiguration(), 'formEditor.formElementGroups', '.')
        );

        $result['formElements'][$formElementType]['creatable'] = in_array(
            $formElementGroup,
            $formElementGroups,
            true
        );

        $this->extractorDto->setResult($result);
    }
}
