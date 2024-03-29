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

use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class AddHmacDataToFormElementPropertyConverter extends AbstractConverter
{
    /**
     * @param mixed $value
     */
    public function __invoke(string $key, $value): void
    {
        $formDefinition = $this->converterDto->getFormDefinition();

        $propertyPathParts = explode('.', $key);
        $lastKeySegment = array_pop($propertyPathParts);
        $propertyPathParts[] = '_orig_' . $lastKeySegment;

        $hashService = GeneralUtility::makeInstance(HashService::class);
        $hmacValuePath = implode('.', array_merge($this->converterDto->getRenderablePathParts(), $propertyPathParts));
        $hmacValue = [
            'value' => $value,
            'hmac' => $hashService->hmac(serialize([$this->converterDto->getFormElementIdentifier(), $key, $value]), $this->sessionToken),
        ];

        $formDefinition = ArrayUtility::setValueByPath($formDefinition, $hmacValuePath, $hmacValue, '.');

        $this->converterDto->setFormDefinition($formDefinition);
    }
}
