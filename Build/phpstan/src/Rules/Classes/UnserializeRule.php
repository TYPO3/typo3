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

namespace TYPO3\CMS\PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Arrays\AllowedArrayKeysTypes;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

/**
 * Custom PHPStan rule to harden `unserialize` calls.
 *
 * Extracted from https://github.com/phpstan/phpstan-src/pull/4754, may become
 * obsolete once this is merged upstream in PHPStan, and can be removed then.
 *
 * @implements Rule<FuncCall>
 */
final readonly class UnserializeRule implements Rule
{
    public function __construct(
        private PhpVersion $phpVersion,
        private ReflectionProvider $reflectionProvider,
    ) {}

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!($node->name instanceof Node\Name)) {
            return [];
        }

        if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
            return [];
        }

        $functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);

        // Skip unsupported functions
        if ($functionReflection->getName() !== 'unserialize') {
            return [];
        }

        // Normalize function arguments
        $normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments(
            ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $node->getArgs(),
                $functionReflection->getVariants(),
                $functionReflection->getNamedArgumentsVariants(),
            ),
            $node,
        );

        // Skip on invalid function signatures
        if ($normalizedFuncCall === null) {
            return [];
        }

        $args = $normalizedFuncCall->getArgs();

        // Fail if $options array is missing in unserialize call
        if (count($args) !== 2) {
            return [
                RuleErrorBuilder::message(
                    'Calling unserialize() without parameter $2 options and "allowed_classes" set to false or a list of allowed class names is insecure.',
                )->identifier('unserialize.options.missing')->build(),
            ];
        }

        $type = $scope->getType($args[1]->value);
        $optionsArrays = $type->getConstantArrays();

        // Early return if $options parameter is not an array
        // (this is already handled by PHPStan's reflection provider)
        if ($optionsArrays === []) {
            return [];
        }

        $allowedClassesChecked = false;
        $errors = [];

        foreach ($optionsArrays as $optionsArray) {
            foreach ($optionsArray->getValueTypes() as $i => $valueType) {
                $key = $optionsArray->getKeyTypes()[$i]->getValue();

                switch ($key) {
                    case 'allowed_classes':
                        $allowedClassesChecked = true;
                        $errors = [
                            ...$errors,
                            ...$this->checkAllowedClasses($valueType),
                        ];
                        break;
                    case 'max_depth':
                        if ($valueType->isInteger()->no()) {
                            $errors[] = RuleErrorBuilder::message(sprintf(
                                'Parameter #2 $options to function unserialize contains an invalid value %s for "max_depth".',
                                $valueType->describe(VerbosityLevel::value()),
                            ))->identifier('unserialize.maxDepth.invalidType')->build();
                        }
                        break;
                    default:
                        $errors[] = RuleErrorBuilder::message(sprintf(
                            'Parameter #2 $options to function unserialize contains unsupported option "%s".',
                            $key,
                        ))->identifier('unserialize.unsupported')->build();
                }
            }
        }

        if (!$allowedClassesChecked) {
            $errors[] = RuleErrorBuilder::message(
                'Parameter #2 $options to function unserialize must be present with "allowed_classes" set to false or a list of allowed class names.',
            )->identifier('unserialize.allowedClasses.missing')->build();
        }

        return $errors;
    }

    /**
     * @return list<RuleError>
     */
    private function checkAllowedClasses(Type $valueType): array
    {
        // $options = false (disallow all classes)
        if ($valueType instanceof ConstantBooleanType && $valueType->getValue() === false) {
            return [];
        }

        // $options = true (allow all classes)
        if ($valueType instanceof ConstantBooleanType && $valueType->getValue() === true) {
            return [
                RuleErrorBuilder::message(
                    'Parameter #2 $options to function unserialize must either be false or a list of allowed class names.',
                )->identifier('unserialize.allowedClasses.insecure')->build(),
            ];
        }

        $allowedArrayType = new ArrayType(AllowedArrayKeysTypes::getType($this->phpVersion), new ClassStringType());
        $allowedType = TypeCombinator::union(new BooleanType(), $allowedArrayType);

        // Invalid value type
        if (!$allowedType->accepts($valueType, true)->yes()) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Parameter #2 $options to function unserialize contains an invalid value %s for "allowed_classes".',
                    $valueType->describe(VerbosityLevel::value()),
                ))->identifier('unserialize.allowedClasses.invalidType')->build(),
            ];
        }

        $errors = [];

        foreach ($valueType->getConstantArrays() as $optionConstantArray) {
            foreach ($optionConstantArray->getValueTypes() as $j => $itemType) {
                // Unsupported value type
                if (!$itemType->isString()->yes()) {
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'Parameter #2 $options to function unserialize contains an invalid value for "allowed_classes" item #%d.',
                        $j + 1,
                    ))->identifier('unserialize.allowedClasses.invalidType')->build();
                }
            }
        }

        return $errors;
    }
}
