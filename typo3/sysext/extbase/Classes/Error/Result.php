<?php
namespace TYPO3\CMS\Extbase\Error;

/*                                                                        *
 * This script belongs to the Extbase framework                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * Result object for operations dealing with objects, such as the Property Mapper or the Validators.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
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
    protected $parent = null;

    /**
     * Injects the parent result and propagates the
     * cached error states upwards
     *
     * @param Result $parent
     */
    public function setParent(Result $parent)
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
     * @api
     */
    public function addError(Error $error)
    {
        $this->errors[] = $error;
        $this->setErrorsExist();
    }

    /**
     * Add a warning to the current Result object
     *
     * @param Warning $warning
     * @api
     */
    public function addWarning(Warning $warning)
    {
        $this->warnings[] = $warning;
        $this->setWarningsExist();
    }

    /**
     * Add a notice to the current Result object
     *
     * @param Notice $notice
     * @api
     */
    public function addNotice(Notice $notice)
    {
        $this->notices[] = $notice;
        $this->setNoticesExist();
    }

    /**
     * Get all errors in the current Result object (non-recursive)
     *
     * @return Error[]
     * @api
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get all warnings in the current Result object (non-recursive)
     *
     * @return Warning[]
     * @api
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Get all notices in the current Result object (non-recursive)
     *
     * @return Notice[]
     * @api
     */
    public function getNotices()
    {
        return $this->notices;
    }

    /**
     * Get the first error object of the current Result object (non-recursive)
     *
     * @return Error
     * @api
     */
    public function getFirstError()
    {
        reset($this->errors);
        return current($this->errors);
    }

    /**
     * Get the first warning object of the current Result object (non-recursive)
     *
     * @return Warning
     * @api
     */
    public function getFirstWarning()
    {
        reset($this->warnings);
        return current($this->warnings);
    }

    /**
     * Get the first notice object of the curren Result object (non-recursive)
     *
     * @return Notice
     * @api
     */
    public function getFirstNotice()
    {
        reset($this->notices);
        return current($this->notices);
    }

    /**
     * Return a Result object for the given property path. This is
     * a fluent interface, so you will proboably use it like:
     * $result->forProperty('foo.bar')->getErrors() -- to get all errors
     * for property "foo.bar"
     *
     * @param string $propertyPath
     * @return Result
     * @api
     */
    public function forProperty($propertyPath)
    {
        if ($propertyPath === '' || $propertyPath === null) {
            return $this;
        }
        if (strpos($propertyPath, '.') !== false) {
            return $this->recurseThroughResult(explode('.', $propertyPath));
        }
        if (!isset($this->propertyResults[$propertyPath])) {
            $this->propertyResults[$propertyPath] = new self();
            $this->propertyResults[$propertyPath]->setParent($this);
        }
        return $this->propertyResults[$propertyPath];
    }

    /**
     * Internal use only!
     *
     * @param array $pathSegments
     * @return Result
     */
    public function recurseThroughResult(array $pathSegments)
    {
        if (empty($pathSegments)) {
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
    protected function setErrorsExist()
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
    protected function setWarningsExist()
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
    protected function setNoticesExist()
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
    public function hasMessages()
    {
        return $this->errorsExist || $this->noticesExist || $this->warningsExist;
    }

    /**
     * Clears the result
     */
    public function clear()
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
     * Internal use only!
     *
     * @param string $propertyName
     * @param string $checkerMethodName
     * @return bool
     */
    protected function hasProperty($propertyName, $checkerMethodName)
    {
        if (!empty($this->{$propertyName})) {
            return true;
        }
        foreach ($this->propertyResults as $subResult) {
            if ($subResult->{$checkerMethodName}()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Does the current Result object have Errors? (Recursively)
     *
     * @return bool
     * @api
     */
    public function hasErrors()
    {
        return $this->hasProperty('errors', 'hasErrors');
    }

    /**
     * Does the current Result object have Warnings? (Recursively)
     *
     * @return bool
     * @api
     */
    public function hasWarnings()
    {
        return $this->hasProperty('warnings', 'hasWarnings');
    }

    /**
     * Does the current Result object have Notices? (Recursively)
     *
     * @return bool
     * @api
     */
    public function hasNotices()
    {
        return $this->hasProperty('notices', 'hasNotices');
    }

    /**
     * Get a list of all Error objects recursively. The result is an array,
     * where the key is the property path where the error occurred, and the
     * value is a list of all errors (stored as array)
     *
     * @return Error[]
     * @api
     */
    public function getFlattenedErrors()
    {
        $result = [];
        $this->flattenTree('errors', $result, []);
        return $result;
    }

    /**
     * Get a list of all Warning objects recursively. The result is an array,
     * where the key is the property path where the warning occurred, and the
     * value is a list of all warnings (stored as array)
     *
     * @return Warning[]
     * @api
     */
    public function getFlattenedWarnings()
    {
        $result = [];
        $this->flattenTree('warnings', $result, []);
        return $result;
    }

    /**
     * Get a list of all Notice objects recursively. The result is an array,
     * where the key is the property path where the notice occurred, and the
     * value is a list of all notices (stored as array)
     *
     * @return Notice[]
     * @api
     */
    public function getFlattenedNotices()
    {
        $result = [];
        $this->flattenTree('notices', $result, []);
        return $result;
    }

    /**
     * Only use internally!
     *
     * Flatten a tree of Result objects, based on a certain property.
     *
     * @param string $propertyName
     * @param array $result
     * @param array $level
     */
    public function flattenTree($propertyName, &$result, $level)
    {
        if (!empty($this->$propertyName)) {
            $result[implode('.', $level)] = $this->$propertyName;
        }
        foreach ($this->propertyResults as $subPropertyName => $subResult) {
            $level[] = $subPropertyName;
            $subResult->flattenTree($propertyName, $result, $level);
            array_pop($level);
        }
    }

    /**
     * Merge the given Result object into this one.
     *
     * @param Result $otherResult
     * @api
     */
    public function merge(Result $otherResult)
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
            /** @var $subResult Result */
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
    protected function mergeProperty(Result $otherResult, $getterName, $adderName)
    {
        foreach ($otherResult->$getterName() as $messageInOtherResult) {
            $this->$adderName($messageInOtherResult);
        }
    }

    /**
     * Get a list of all sub Result objects available.
     *
     * @return Result[]
     */
    public function getSubResults()
    {
        return $this->propertyResults;
    }
}
