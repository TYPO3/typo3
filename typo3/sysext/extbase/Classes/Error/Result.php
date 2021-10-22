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

namespace TYPO3\CMS\Extbase\Error;

/**
 * Result object for operations dealing with objects, such as the Property Mapper or the Validators.
 */
class Result
{
    /**
     * @var Error[]
     */
    protected $errors = [];

    /**
     * Caches the existence of errors
     * @var bool
     */
    protected $errorsExist = false;

    /**
     * @var Warning[]
     */
    protected $warnings = [];

    /**
     * Caches the existence of warning
     * @var bool
     */
    protected $warningsExist = false;

    /**
     * @var Notice[]
     */
    protected $notices = [];

    /**
     * Caches the existence of notices
     * @var bool
     */
    protected $noticesExist = false;

    /**
     * The result objects for the sub properties
     *
     * @var Result[]
     */
    protected $propertyResults = [];

    /**
     * @var Result
     */
    protected $parent;

    /**
     * Injects the parent result and propagates the
     * cached error states upwards
     *
     * @param Result $parent
     */
    public function setParent(Result $parent): void
    {
        if ($this->parent !== $parent) {
            $this->parent = $parent;
            if ($this->hasErrors()) {
                $parent->setErrorsExist();
            }
            if ($this->hasWarnings()) {
                $parent->setWarningsExist();
            }
            if ($this->hasNotices()) {
                $parent->setNoticesExist();
            }
        }
    }

    /**
     * Add an error to the current Result object
     *
     * @param Error $error
     */
    public function addError(Error $error): void
    {
        $this->errors[] = $error;
        $this->setErrorsExist();
    }

    /**
     * Add a warning to the current Result object
     *
     * @param Warning $warning
     */
    public function addWarning(Warning $warning): void
    {
        $this->warnings[] = $warning;
        $this->setWarningsExist();
    }

    /**
     * Add a notice to the current Result object
     *
     * @param Notice $notice
     */
    public function addNotice(Notice $notice): void
    {
        $this->notices[] = $notice;
        $this->setNoticesExist();
    }

    /**
     * Get all errors in the current Result object (non-recursive)
     *
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all warnings in the current Result object (non-recursive)
     *
     * @return Warning[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get all notices in the current Result object (non-recursive)
     *
     * @return Notice[]
     */
    public function getNotices(): array
    {
        return $this->notices;
    }

    /**
     * Get the first error object of the current Result object (non-recursive)
     *
     * @return bool|Error
     */
    public function getFirstError()
    {
        reset($this->errors);
        return current($this->errors);
    }

    /**
     * Get the first warning object of the current Result object (non-recursive)
     *
     * @return bool|Warning
     */
    public function getFirstWarning()
    {
        reset($this->warnings);
        return current($this->warnings);
    }

    /**
     * Get the first notice object of the current Result object (non-recursive)
     *
     * @return bool|Notice
     */
    public function getFirstNotice()
    {
        reset($this->notices);
        return current($this->notices);
    }

    /**
     * Return a Result object for the given property path. This is
     * a fluent interface, so you will probably use it like:
     * $result->forProperty('foo.bar')->getErrors() -- to get all errors
     * for property "foo.bar"
     *
     * @param string|null $propertyPath
     * @return Result
     */
    public function forProperty(?string $propertyPath): Result
    {
        if ($propertyPath === '' || $propertyPath === null) {
            return $this;
        }
        if (str_contains($propertyPath, '.')) {
            return $this->recurseThroughResult(explode('.', $propertyPath));
        }
        if (!isset($this->propertyResults[$propertyPath])) {
            $this->propertyResults[$propertyPath] = new self();
            $this->propertyResults[$propertyPath]->setParent($this);
        }
        return $this->propertyResults[$propertyPath];
    }

    /**
     * @todo: consider making this method protected as it will and should not be called from an outside scope
     *
     * @param array $pathSegments
     * @return Result
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function recurseThroughResult(array $pathSegments): Result
    {
        if (count($pathSegments) === 0) {
            return $this;
        }

        $propertyName = array_shift($pathSegments);

        if (!isset($this->propertyResults[$propertyName])) {
            $this->propertyResults[$propertyName] = new self();
            $this->propertyResults[$propertyName]->setParent($this);
        }

        return $this->propertyResults[$propertyName]->recurseThroughResult($pathSegments);
    }

    /**
     * Sets the error cache to TRUE and propagates the information
     * upwards the Result-Object Tree
     */
    protected function setErrorsExist(): void
    {
        $this->errorsExist = true;
        if ($this->parent !== null) {
            $this->parent->setErrorsExist();
        }
    }

    /**
     * Sets the warning cache to TRUE and propagates the information
     * upwards the Result-Object Tree
     */
    protected function setWarningsExist(): void
    {
        $this->warningsExist = true;
        if ($this->parent !== null) {
            $this->parent->setWarningsExist();
        }
    }

    /**
     * Sets the notices cache to TRUE and propagates the information
     * upwards the Result-Object Tree
     */
    protected function setNoticesExist(): void
    {
        $this->noticesExist = true;
        if ($this->parent !== null) {
            $this->parent->setNoticesExist();
        }
    }

    /**
     * Does the current Result object have Notices, Errors or Warnings? (Recursively)
     *
     * @return bool
     */
    public function hasMessages(): bool
    {
        return $this->errorsExist || $this->noticesExist || $this->warningsExist;
    }

    /**
     * Clears the result
     */
    public function clear(): void
    {
        $this->errors = [];
        $this->notices = [];
        $this->warnings = [];

        $this->warningsExist = false;
        $this->noticesExist = false;
        $this->errorsExist = false;

        $this->propertyResults = [];
    }

    /**
     * Does the current Result object have Errors? (Recursively)
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        if (count($this->errors) > 0) {
            return true;
        }

        foreach ($this->propertyResults as $subResult) {
            if ($subResult->hasErrors()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the current Result object have Warnings? (Recursively)
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        if (count($this->warnings) > 0) {
            return true;
        }

        foreach ($this->propertyResults as $subResult) {
            if ($subResult->hasWarnings()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the current Result object have Notices? (Recursively)
     *
     * @return bool
     */
    public function hasNotices(): bool
    {
        if (count($this->notices) > 0) {
            return true;
        }

        foreach ($this->propertyResults as $subResult) {
            if ($subResult->hasNotices()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a list of all Error objects recursively. The result is an array,
     * where the key is the property path where the error occurred, and the
     * value is a list of all errors (stored as array)
     *
     * @return array<string,array<Error>>
     */
    public function getFlattenedErrors(): array
    {
        $result = [];
        $this->flattenErrorTree($result, []);
        return $result;
    }

    /**
     * Get a list of all Warning objects recursively. The result is an array,
     * where the key is the property path where the warning occurred, and the
     * value is a list of all warnings (stored as array)
     *
     * @return array<string,array<Warning>>
     */
    public function getFlattenedWarnings(): array
    {
        $result = [];
        $this->flattenWarningsTree($result, []);
        return $result;
    }

    /**
     * Get a list of all Notice objects recursively. The result is an array,
     * where the key is the property path where the notice occurred, and the
     * value is a list of all notices (stored as array)
     *
     * @return array<string,array<Notice>>
     */
    public function getFlattenedNotices(): array
    {
        $result = [];
        $this->flattenNoticesTree($result, []);
        return $result;
    }

    /**
     * @param array $result
     * @param array $level
     */
    protected function flattenErrorTree(array &$result, array $level): void
    {
        if (count($this->errors) > 0) {
            $result[implode('.', $level)] = $this->errors;
        }
        foreach ($this->propertyResults as $subPropertyName => $subResult) {
            $level[] = $subPropertyName;
            $subResult->flattenErrorTree($result, $level);
            array_pop($level);
        }
    }

    /**
     * @param array $result
     * @param array $level
     */
    protected function flattenWarningsTree(array &$result, array $level): void
    {
        if (count($this->warnings) > 0) {
            $result[implode('.', $level)] = $this->warnings;
        }
        foreach ($this->propertyResults as $subPropertyName => $subResult) {
            $level[] = $subPropertyName;
            $subResult->flattenWarningsTree($result, $level);
            array_pop($level);
        }
    }

    /**
     * @param array $result
     * @param array $level
     */
    protected function flattenNoticesTree(array &$result, array $level): void
    {
        if (count($this->notices) > 0) {
            $result[implode('.', $level)] = $this->notices;
        }
        foreach ($this->propertyResults as $subPropertyName => $subResult) {
            $level[] = $subPropertyName;
            $subResult->flattenNoticesTree($result, $level);
            array_pop($level);
        }
    }

    /**
     * Merge the given Result object into this one.
     *
     * @param Result $otherResult
     */
    public function merge(Result $otherResult): void
    {
        if ($otherResult->errorsExist) {
            $this->mergeProperty($otherResult, 'getErrors', 'addError');
        }
        if ($otherResult->warningsExist) {
            $this->mergeProperty($otherResult, 'getWarnings', 'addWarning');
        }
        if ($otherResult->noticesExist) {
            $this->mergeProperty($otherResult, 'getNotices', 'addNotice');
        }

        foreach ($otherResult->getSubResults() as $subPropertyName => $subResult) {
            /** @var Result $subResult */
            if (array_key_exists($subPropertyName, $this->propertyResults) && $this->propertyResults[$subPropertyName]->hasMessages()) {
                $this->forProperty($subPropertyName)->merge($subResult);
            } else {
                $this->propertyResults[$subPropertyName] = $subResult;
                $subResult->setParent($this);
            }
        }
    }

    /**
     * Merge a single property from the other result object.
     *
     * @param Result $otherResult
     * @param string $getterName
     * @param string $adderName
     */
    protected function mergeProperty(Result $otherResult, string $getterName, string $adderName): void
    {
        $getter = [$otherResult, $getterName];
        $adder = [$this, $adderName];

        if (!is_callable($getter) || !is_callable($adder)) {
            return;
        }

        foreach ($getter() as $messageInOtherResult) {
            $adder($messageInOtherResult);
        }
    }

    /**
     * Get a list of all sub Result objects available.
     *
     * @return Result[]
     */
    public function getSubResults(): array
    {
        return $this->propertyResults;
    }
}
