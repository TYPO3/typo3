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

/**
 * Class TYPO3\CMS\Extbase\Validation\ValidatorClassNameResolver
 */
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
     * @param string $validatorIdentifier
     * @return string
     * @throws NoSuchValidatorException
     * @internal
     */
    public static function resolve(string $validatorIdentifier): string
    {
        // Trim leading slash if $validatorName is FQCN like \TYPO3\CMS\Extbase\Validation\Validator\FloatValidator
        $validatorIdentifier = ltrim($validatorIdentifier, '\\');

        $validatorClassName = $validatorIdentifier;
        if (strpbrk($validatorIdentifier, ':') !== false) {
            // Found shorthand validator, either extbase or foreign extension
            // NotEmpty or Acme.MyPck.Ext:MyValidator
            [$vendorNamespace, $validatorBaseName] = explode(':', $validatorIdentifier);

            // todo: at this point ($validatorIdentifier !== $vendorNamespace) is always true as $validatorIdentifier
            // todo: contains a colon ":" and $vendorNamespace doesn't.
            if ($validatorIdentifier !== $vendorNamespace && $validatorBaseName !== '') {
                // Shorthand custom
                if (str_contains($vendorNamespace, '.')) {
                    $extensionNameParts = explode('.', $vendorNamespace);
                    $vendorNamespace = array_pop($extensionNameParts);
                    $vendorName = implode('\\', $extensionNameParts);
                    $validatorClassName = $vendorName . '\\' . $vendorNamespace . '\\Validation\\Validator\\' . $validatorBaseName;
                }
            } else {
                // todo: the only way to reach this path is to use a validator identifier like "Integer:"
                // todo: as we are using $validatorIdentifier here, this path always fails.
                // Shorthand built in
                $validatorClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\' . self::getValidatorType($validatorIdentifier);
            }
        } elseif (strpbrk($validatorIdentifier, '\\') === false) {
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
        switch ($type) {
            case 'int':
                $type = 'Integer';
                break;
            case 'bool':
                $type = 'Boolean';
                break;
            case 'double':
                $type = 'Float';
                break;
            case 'numeric':
                $type = 'Number';
                break;
            default:
                $type = ucfirst($type);
        }
        return $type;
    }
}
