<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\ExtensionScanner\Php\Matcher;

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

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\New_;

/**
 * Finds invocations to class constructors and the amount of passed arguments.
 * This matcher supports direct `new MyClass(123)` invocations as well as delegated
 * calls to `GeneralUtility::makeInstance(MyClass::class, 123)` using `GeneratorClassResolver`.
 *
 * These configuration property names are handled independently:
 * + numberOfMandatoryArguments
 * + maximumNumberOfArguments
 * + unusedArgumentNumbers
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class ConstructorArgumentMatcher extends AbstractCoreMatcher
{
    protected const TOPIC_TYPE_REQUIRED = 'required';
    protected const TOPIC_TYPE_DROPPED = 'dropped';
    protected const TOPIC_TYPE_CALLED = 'called';
    protected const TOPIC_TYPE_UNUSED = 'unused';

    /**
     * Prepare $this->flatMatcherDefinitions once and validate config
     *
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitionsTopicRequirements([
            self::TOPIC_TYPE_REQUIRED => ['numberOfMandatoryArguments'],
            self::TOPIC_TYPE_DROPPED => ['maximumNumberOfArguments'],
            self::TOPIC_TYPE_CALLED => ['numberOfMandatoryArguments', 'maximumNumberOfArguments'],
            self::TOPIC_TYPE_UNUSED => ['unusedArgumentNumbers'],
        ]);
    }

    /**
     * Called by PhpParser.
     * Test for "->deprecated()" (weak match)
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        if ($this->isFileIgnored($node) || $this->isLineIgnored($node)) {
            return;
        }
        $resolvedNode = $node->getAttribute(self::NODE_RESOLVED_AS, null) ?? $node;
        if (!$resolvedNode instanceof New_
            || !isset($resolvedNode->class)
            || is_object($node->class) && !method_exists($node->class, '__toString')
            || !array_key_exists((string)$resolvedNode->class, $this->matcherDefinitions)
        ) {
            return;
        }

        // A method call is considered a match if it is not called with argument unpacking
        // and number of used arguments is lower than numberOfMandatoryArguments
        if ($this->isArgumentUnpackingUsed($resolvedNode->args)) {
            return;
        }

        // $node reflects invocation, e.g. `GeneralUtility::makeInstance(MyClass::class, 123)`
        // $resolvedNode reflects resolved and actual usage, e.g. `new MyClass(123)`
        $this->handleRequiredArguments($node, $resolvedNode);
        $this->handleDroppedArguments($node, $resolvedNode);
        $this->handleCalledArguments($node, $resolvedNode);
        $this->handleUnusedArguments($node, $resolvedNode);
    }

    /**
     * @param Node $node reflects invocation, e.g. `GeneralUtility::makeInstance(MyClass::class, 123)`
     * @param Node $resolvedNode reflects resolved and actual usage, e.g. `new MyClass(123)`
     * @return bool
     */
    protected function handleRequiredArguments(Node $node, Node $resolvedNode): bool
    {
        $className = (string)$resolvedNode->class;
        $candidate = $this->matcherDefinitions[$className][self::TOPIC_TYPE_REQUIRED] ?? null;
        $mandatoryArguments = $candidate['numberOfMandatoryArguments'] ?? null;
        $numberOfArguments = count($resolvedNode->args);

        if ($candidate === null || $numberOfArguments >= $mandatoryArguments) {
            return false;
        }

        $this->matches[] = [
            'restFiles' => $candidate['restFiles'],
            'line' => $node->getAttribute('startLine'),
            'message' => sprintf(
                '%s::__construct requires at least %d arguments (%d given).',
                $className,
                $mandatoryArguments,
                $numberOfArguments
            ),
            'indicator' => 'strong',
        ];
        return true;
    }

    /**
     * @param Node $node reflects invocation, e.g. `GeneralUtility::makeInstance(MyClass::class, 123)`
     * @param Node $resolvedNode reflects resolved and actual usage, e.g. `new MyClass(123)`
     * @return bool
     */
    protected function handleDroppedArguments(Node $node, Node $resolvedNode): bool
    {
        $className = (string)$resolvedNode->class;
        $candidate = $this->matcherDefinitions[$className][self::TOPIC_TYPE_DROPPED] ?? null;
        $maximumArguments = $candidate['maximumNumberOfArguments'] ?? null;
        $numberOfArguments = count($resolvedNode->args);

        if ($candidate === null || $numberOfArguments <= $maximumArguments) {
            return false;
        }

        $this->matches[] = [
            'restFiles' => $candidate['restFiles'],
            'line' => $node->getAttribute('startLine'),
            'message' => sprintf(
                '%s::__construct supports only %d arguments (%d given).',
                $className,
                $maximumArguments,
                $numberOfArguments
            ),
            'indicator' => 'strong',
        ];
        return true;
    }

    /**
     * @param Node $node reflects invocation, e.g. `GeneralUtility::makeInstance(MyClass::class, 123)`
     * @param Node $resolvedNode reflects resolved and actual usage, e.g. `new MyClass(123)`
     * @return bool
     */
    protected function handleCalledArguments(Node $node, Node $resolvedNode): bool
    {
        $className = (string)$resolvedNode->class;
        $candidate = $this->matcherDefinitions[$className][self::TOPIC_TYPE_CALLED] ?? null;
        $isArgumentUnpackingUsed = $this->isArgumentUnpackingUsed($resolvedNode->args);
        $mandatoryArguments = $candidate['numberOfMandatoryArguments'] ?? null;
        $maximumArguments = $candidate['maximumNumberOfArguments'] ?? null;
        $numberOfArguments = count($resolvedNode->args);

        if ($candidate === null
            || !$isArgumentUnpackingUsed
            && ($numberOfArguments < $mandatoryArguments || $numberOfArguments > $maximumArguments)) {
            return false;
        }

        $this->matches[] = [
            'restFiles' => $candidate['restFiles'],
            'line' => $node->getAttribute('startLine'),
            'message' => sprintf(
                '%s::__construct being called (%d arguments given).',
                $className,
                $numberOfArguments
            ),
            'indicator' => 'weak',
        ];
        return true;
    }

    /**
     * @param Node $node reflects invocation, e.g. `GeneralUtility::makeInstance(MyClass::class, 123)`
     * @param Node $resolvedNode reflects resolved and actual usage, e.g. `new MyClass(123)`
     * @return bool
     */
    protected function handleUnusedArguments(Node $node, Node $resolvedNode): bool
    {
        $className = (string)$resolvedNode->class;
        $candidate = $this->matcherDefinitions[$className][self::TOPIC_TYPE_UNUSED] ?? null;
        // values in array (if any) are actual position counts
        // e.g. `[2, 4]` refers to internal argument indexes `[1, 3]`
        $unusedArgumentPositions = $candidate['unusedArgumentNumbers'] ?? null;

        if ($candidate === null || empty($unusedArgumentPositions)) {
            return false;
        }

        $arguments = $resolvedNode->args;
        // keeping positions having argument values that are not null
        $unusedArgumentPositions = array_filter(
            $unusedArgumentPositions,
            function (int $position) use ($arguments) {
                $index = $position - 1;
                return isset($arguments[$index]->value)
                    && !$arguments[$index]->value instanceof ConstFetch
                    && (
                        !isset($arguments[$index]->value->name->name->parts[0])
                        || $arguments[$index]->value->name->name->parts[0] !== null
                    );
            }
        );
        if (empty($unusedArgumentPositions)) {
            return false;
        }

        $this->matches[] = [
            'restFiles' => $candidate['restFiles'],
            'line' => $node->getAttribute('startLine'),
            'message' => sprintf(
                '%s::__construct was called with argument positions %s not being null.',
                $className,
                implode(', ', $unusedArgumentPositions)
            ),
            'indicator' => 'strong',
        ];
        return true;
    }

    protected function validateMatcherDefinitionsTopicRequirements(array $topicRequirements): void
    {
        foreach ($this->matcherDefinitions as $key => $matcherDefinition) {
            foreach ($topicRequirements as $topic => $requiredArrayKeys) {
                if (empty($matcherDefinition[$topic])) {
                    continue;
                }
                $this->validateMatcherDefinitionKeys($key, $matcherDefinition[$topic], $requiredArrayKeys);
            }
        }
    }
}
