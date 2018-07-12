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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class AddHmacDataToPropertyCollectionElementConverter extends AbstractConverter
{

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __invoke(string $key, $value): void
    {
        $formDefinition = $this->converterDto->getFormDefinition();

        $propertyPathParts = explode('.', $key);
        $lastKeySegment = array_pop($propertyPathParts);
        $propertyPathParts[] = '_orig_' . $lastKeySegment;

        $hmacValuePath = implode('.', array_merge(
            $this->converterDto->getRenderablePathParts(),
            [$this->converterDto->getPropertyCollectionName(), $this->converterDto->getPropertyCollectionIndex()],
            $propertyPathParts
        ));

        $hmacValue = [
            'value' => $value,
            'hmac' => GeneralUtility::hmac(
                serialize([
                    $this->converterDto->getFormElementIdentifier(),
                    $this->converterDto->getPropertyCollectionName(),
                    $this->converterDto->getPropertyCollectionElementIdentifier(),
                    $key,
                    $value
                ]),
                $this->sessionToken
            )
        ];

        $formDefinition = ArrayUtility::setValueByPath($formDefinition, $hmacValuePath, $hmacValue, '.');

        $this->converterDto->setFormDefinition($formDefinition);
    }
}
