<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters;

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

/**
 * @internal
 */
class FinisherTranslationLanguageConverter extends AbstractConverter
{

    /**
     * If "finishers.x.options.translation.language" is empty then set the value to "default" and remove
     * the hmac.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __invoke(string $key, $value): void
    {
        if (!empty($value)) {
            return;
        }

        $formDefinition = $this->converterDto->getFormDefinition();

        $formDefinition = ArrayUtility::setValueByPath($formDefinition, $key, 'default', '.');

        $hmacPropertyPathParts = explode('.', $key);
        $lastKeySegment = array_pop($hmacPropertyPathParts);
        $hmacPropertyPathParts[] = '_orig_' . $lastKeySegment;
        $hmacValuePath = implode('.', $hmacPropertyPathParts);

        if (ArrayUtility::isValidPath($formDefinition, $hmacValuePath, '.')) {
            $formDefinition = ArrayUtility::removeByPath($formDefinition, $hmacValuePath, '.');
        }

        $this->converterDto->setFormDefinition($formDefinition);
    }
}
