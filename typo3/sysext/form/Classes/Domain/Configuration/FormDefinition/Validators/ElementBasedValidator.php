<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators;

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
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;

/**
 * @internal
 */
abstract class ElementBasedValidator extends AbstractValidator
{

    /**
     * Throws an exception if value from a form element property
     * does not match its hmac hash or if there is no hmac hash
     * available for the value.
     *
     * @param array $currentElement
     * @param mixed $value
     * @param string $sessionToken
     * @param ValidationDto $dto
     * @throws PropertyException
     */
    public function validateFormElementPropertyValueByHmacData(
        array $currentElement,
        $value,
        string $sessionToken,
        ValidationDto $dto
    ): void {
        $hmacDataPath = $this->buildHmacDataPath($dto->getPropertyPath());
        if (ArrayUtility::isValidPath($currentElement, $hmacDataPath, '.')) {
            $hmacData = ArrayUtility::getValueByPath($currentElement, $hmacDataPath, '.');

            $hmacContent = [$dto->getFormElementIdentifier(), $dto->getPropertyPath()];
            if (!$this->getFormDefinitionValidationService()->isPropertyValueEqualToHistoricalValue($hmacContent, $value, $hmacData, $sessionToken)) {
                $message = 'The value "%s" of property "%s" (form element "%s") is not equal to the historical value "%s" #1528588036';
                throw new PropertyException(
                    sprintf(
                        $message,
                        $value,
                        $dto->getPropertyPath(),
                        $dto->getFormElementIdentifier(),
                        $hmacData['value'] ?? ''
                    ),
                    1528588036
                );
            }
        } else {
            $message = 'No hmac found for property "%s" (form element "%s") #1528588037';
            throw new PropertyException(
                sprintf($message, $dto->getPropertyPath(), $dto->getFormElementIdentifier()),
                1528588037
            );
        }
    }
}
