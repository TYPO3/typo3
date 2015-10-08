<?php
namespace TYPO3\CMS\Extbase\Reflection;

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
 * Extended version of the ReflectionProperty
 */
class PropertyReflection extends \ReflectionProperty
{
    /**
     * @var DocCommentParser An instance of the doc comment parser
     */
    protected $docCommentParser;

    /**
     * Checks if the doc comment of this property is tagged with
     * the specified tag
     *
     * @param string $tag Tag name to check for
     * @return bool TRUE if such a tag has been defined, otherwise FALSE
     */
    public function isTaggedWith($tag)
    {
        $result = $this->getDocCommentParser()->isTaggedWith($tag);
        return $result;
    }

    /**
     * Returns an array of tags and their values
     *
     * @return array Tags and values
     */
    public function getTagsValues()
    {
        return $this->getDocCommentParser()->getTagsValues();
    }

    /**
     * Returns the values of the specified tag
     *
     * @param string $tag
     * @return array Values of the given tag
     */
    public function getTagValues($tag)
    {
        return $this->getDocCommentParser()->getTagValues($tag);
    }

    /**
     * Returns the value of the reflected property - even if it is protected.
     *
     * @param object $object Instance of the declaring class \TYPO3\CMS\Extbase\Reflection to read the value from
     * @return mixed Value of the property
     * @throws Exception
     * @todo Maybe support private properties as well, as of PHP 5.3.0 we can do
     */
    public function getValue($object = null)
    {
        if (!is_object($object)) {
            throw new Exception('$object is of type ' . gettype($object) . ', instance of class ' . $this->class . ' expected.', 1210859212);
        }
        if ($this->isPublic()) {
            return parent::getValue($object);
        }
        if ($this->isPrivate()) {
            throw new Exception('Cannot return value of private property "' . $this->name . '.', 1210859206);
        }
        parent::setAccessible(true);
        return parent::getValue($object);
    }

    /**
     * Returns an instance of the doc comment parser and
     * runs the parse() method.
     *
     * @return DocCommentParser
     */
    protected function getDocCommentParser()
    {
        if (!is_object($this->docCommentParser)) {
            $this->docCommentParser = new DocCommentParser();
            $this->docCommentParser->parseDocComment($this->getDocComment());
        }
        return $this->docCommentParser;
    }
}
