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

/**
 * @internal
 */
class PropertyCollectionElementHmacDataValidator extends CollectionBasedValidator
{

    /**
     * Checks if the property collection element values matches to its hmac hash.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __invoke(string $key, $value): void
    {
        $dto = $this->validationDto->withPropertyPath($key)->withPropertyCollectionElementIdentifier(
            $this->currentElement['identifier']
        );
        $this->validatePropertyCollectionElementPropertyValueByHmacData(
            $this->currentElement,
            $value,
            $this->sessionToken,
            $dto
        );
    }
}
