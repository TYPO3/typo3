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
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<Node\Expr\Instanceof_>
 */
class UnneededInstanceOfRule implements Rule
{
    public function __construct(
        private readonly bool $treatPhpDocTypesAsCertain,
    ) {
    }

    public function getNodeType(): string
    {
        return Node\Expr\Instanceof_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $instanceofType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node) : $scope->getNativeType($node);

        if ($instanceofType instanceof ConstantBooleanType) {
            return [];
        }
        if (!$instanceofType instanceof BooleanType) {
            return [];
        }
        if (!$node->expr instanceof Node\Expr\Variable) {
            return [];
        }

        if ($node->class instanceof Node\Name) {
            $className = $scope->resolveName($node->class);
            $classType = new ObjectType($className);
        } else {
            $classType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->class) : $scope->getNativeType($node->class);
        }

        $exprType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->expr) : $scope->getNativeType($node->expr);
        if (TypeCombinator::containsNull($exprType)) {
            $nonNullType = TypeCombinator::removeNull($exprType);

            if ($nonNullType instanceof ObjectType && $nonNullType->equals($classType)) {
                return [
                    RuleErrorBuilder::message(
                        sprintf(
                            'Unneeded instanceof on %s|null. Use a null check instead.',
                            $nonNullType->describe(VerbosityLevel::typeOnly())
                        )
                    )->identifier('instanceof.unneededNotNullSubstitute')->build(),
                ];
            }
        }

        return [];
    }
}
