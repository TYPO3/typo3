<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Hooks;

use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto;

final class MyValidationConfigurationHook
{
    /**
     * @return ValidationDto[]
     */
    public function addAdditionalPropertyPaths(ValidationDto $validationDto): array
    {
        $textDto = $validationDto->withFormElementType('Text');
        return [
            $textDto->withPropertyPath('properties.my.custom.property'),
        ];
    }
}
