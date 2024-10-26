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

namespace TYPO3\CMS\Extbase\Validation;

use TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

final class ValidatorClassNameResolver
{
    /**
     * This method is marked internal due to several facts:
     *
     * - The functionality is not 100% tested and still contains some bugs
     * - The functionality might not be needed any longer if Extbase switches
     *   to the symfony/validator component.
     *
     * This method can be used by extension developers. As long as it remains,
     * its functionality will not change. It might even become more stable.
     * However, developers should be aware that this method might vanish
     * without any deprecation.
     *
     * @throws NoSuchValidatorException
     * @internal
     */
    public static function resolve(string $validatorIdentifier): string
    {
        // Trim leading slash if $validatorName is FQCN like \TYPO3\CMS\Extbase\Validation\Validator\FloatValidator
        $validatorIdentifier = ltrim($validatorIdentifier, '\\');
        $validatorClassName = $validatorIdentifier;
        if (strpbrk($validatorIdentifier, '\\') === false) {
            // Shorthand built in
            $validatorClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\' . self::getValidatorType($validatorIdentifier);
        }
        if (!str_ends_with($validatorClassName, 'Validator')) {
            $validatorClassName .= 'Validator';
        }
        if (!class_exists($validatorClassName)) {
            throw new NoSuchValidatorException('Validator class ' . $validatorClassName . ' does not exist', 1365799920);
        }
        if (!is_subclass_of($validatorClassName, ValidatorInterface::class)) {
            throw new NoSuchValidatorException(
                'Validator class ' . $validatorClassName . ' must implement ' . ValidatorInterface::class,
                1365776838
            );
        }
        return $validatorClassName;
    }

    /**
     * Used to map PHP types to validator types.
     *
     * @param string $type Data type to unify
     * @return string unified data type
     */
    private static function getValidatorType(string $type): string
    {
        return match ($type) {
            'int' => 'Integer',
            'bool' => 'Boolean',
            'double' => 'Float',
            'numeric' => 'Number',
            default => ucfirst($type),
        };
    }
}
