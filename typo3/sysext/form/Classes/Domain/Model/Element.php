<?php
namespace TYPO3\CMS\Form\Domain\Model;

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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * The Element Domain Model represents the high-level
 * view on the user submitted data using a nested hierarchy.
 */
class Element extends AbstractEntity
{
    /**
     * This array holds all the additional arguments to use it in the template
     *
     * @var array
     */
    protected $additionalArguments;

    /**
     * child elements
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Form\Domain\Model\Element>
     */
    protected $childElements;

    /**
     * A global counter over all elements
     *
     * @var int
     */
    protected $elementCounter;

    /**
     * The element type (e.g BUTTON)
     *
     * @var string
     */
    protected $elementType;

    /**
     * The validation error messages
     *
     * @var array
     */
    protected $validationErrorMessages;

    /**
     * This array holds all the element html attributes with their values
     *
     * @var array
     */
    protected $htmlAttributes;

    /**
     * The id attribute
     *
     * @var string
     */
    protected $id;

    /**
     * The element layout
     *
     * @var array
     * @deprecated since TYPO3 CMS 7, this property will be removed in TYPO3 CMS 8, as the functionality is now done via fluid
     */
    protected $layout;

    /**
     * The mandatory validation messages
     *
     * @var array
     */
    protected $mandatoryValidationMessages;

    /**
     * The name attribute
     *
     * @var string
     */
    protected $name;

    /**
     * parent element
     *
     * @var \TYPO3\CMS\Form\Domain\Model\Element
     */
    protected $parentElement;

    /**
     * The fluid partial for the element
     *
     * @var string
     */
    protected $partialPath;

    /**
     * TRUE if the element should be displayed
     *
     * @var bool
     */
    protected $showElement;

    /**
     * The theme name
     *
     * @var string
     */
    protected $themeName;

    /**
     * Creates an instance.
     */
    public function __construct()
    {
        $this->initStorageObjects();
    }

    /**
     * Initializes all ObjectStorage properties.
     *
     * @return void
     */
    protected function initStorageObjects()
    {
        $this->childElements = new ObjectStorage();
    }

    /**
     * Return a array with all the additional arguments to use it in the template
     *
     * @return array
     */
    public function getAdditionalArguments()
    {
        return $this->additionalArguments;
    }

    /**
     * Sets a array with all the additional arguments to use it in the template
     *
     * @param array $additionalArguments
     * @return void
     */
    public function setAdditionalArguments($additionalArguments = [])
    {
        $this->additionalArguments = $additionalArguments;
    }

    /**
     * Get a single attribute value
     *
     * @param string $key
     * @return array
     */
    public function getAdditionalArgument($key = '')
    {
        return $this->additionalArguments[$key];
    }

    /**
     * Set a single attribute and value
     *
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function setAdditionalArgument($key = '', $value = null)
    {
        $this->additionalArguments[$key] = $value;
    }

    /**
     * Adds a child element
     *
     * @param \TYPO3\CMS\Form\Domain\Model\Element $element
     * @return void
     */
    public function addChildElement(Element $element)
    {
        $this->childElements->attach($element);
    }

    /**
     * Returns the child elements
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Form\Domain\Model\Element> $element
     */
    public function getChildElements()
    {
        return $this->childElements;
    }

    /**
     * Sets the child elements
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Form\Domain\Model\Element> $childElements
     * @return void
     */
    public function setChildElements(ObjectStorage $childElements)
    {
        $this->childElements = $childElements;
    }

    /**
     * Returns the element counter
     *
     * @return int
     */
    public function getElementCounter()
    {
        return $this->elementCounter;
    }

    /**
     * Sets the element counter
     *
     * @param int $elementCounter
     * @return void
     */
    public function setElementCounter($elementCounter = 0)
    {
        $this->elementCounter = $elementCounter;
    }

    /**
     * Returns the element type
     *
     * @return string
     */
    public function getElementType()
    {
        return $this->elementType;
    }

    /**
     * Returns the element type in lower case
     *
     * @return string
     */
    public function getElementTypeLowerCase()
    {
        return strtolower($this->elementType);
    }

    /**
     * Sets the parent element
     *
     * @param string $elementType
     * @return void
     */
    public function setElementType($elementType)
    {
        $this->elementType = (string)$elementType;
    }

    /**
     * Returns the validation error messages
     *
     * @return array
     */
    public function getValidationErrorMessages()
    {
        return $this->validationErrorMessages;
    }

    /**
     * Sets the validation error messages
     *
     * @param array $validationErrorMessages
     * @return void
     */
    public function setValidationErrorMessages(array $validationErrorMessages)
    {
        $this->validationErrorMessages = $validationErrorMessages;
    }

    /**
     * Returns the element html attributes and values
     *
     * @return array
     */
    public function getHtmlAttributes()
    {
        return $this->htmlAttributes;
    }

    /**
     * Sets the element html attributes and values
     *
     * @param array $htmlAttributes
     * @return void
     */
    public function setHtmlAttributes($htmlAttributes = [])
    {
        $this->htmlAttributes = $htmlAttributes;
    }

    /**
     * Remove a single html attribute
     *
     * @param string $key
     * @return void
     */
    public function removeHtmlAttribute($key = '')
    {
        unset($this->htmlAttributes[$key]);
    }

    /**
     * Get a single html attribute value
     *
     * @param string $key
     * @return array
     */
    public function getHtmlAttribute($key = '')
    {
        return $this->htmlAttributes[$key];
    }

    /**
     * Set a single html attribute and value
     *
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function setHtmlAttribute($key = '', $value = null)
    {
        $this->htmlAttributes[$key] = $value;
    }

    /**
     * Returns the id attribute
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the id attribute
     *
     * @param string $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = (string)$id;
    }

    /**
     * Returns the element layout
     *
     * @return array
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Sets the element layout
     *
     * @param array $layout
     * @return void
     */
    public function setLayout(array $layout)
    {
        $this->layout = $layout;
    }

    /**
     * Returns the mandatory validation messages
     *
     * @return array
     */
    public function getMandatoryValidationMessages()
    {
        return $this->mandatoryValidationMessages;
    }

    /**
     * Sets the mandatory validation messages
     *
     * @param array $mandatoryValidationMessages
     * @return void
     */
    public function setMandatoryValidationMessages(array $mandatoryValidationMessages)
    {
        $this->mandatoryValidationMessages = $mandatoryValidationMessages;
    }

    /**
     * Returns the name attribute
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name attribute
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = (string)$name;
    }

    /**
     * Returns the parent element
     *
     * @return Element
     */
    public function getParentElement()
    {
        return $this->parentElement;
    }

    /**
     * Sets the parent element
     *
     * @param \TYPO3\CMS\Form\Domain\Model\Element
     * @return void
     */
    public function setParentElement(Element $parentElement)
    {
        $this->parentElement = $parentElement;
    }

    /**
     * Returns the fluid partial path for the element
     *
     * @return string
     */
    public function getPartialPath()
    {
        return $this->partialPath;
    }

    /**
     * Sets the fluid partial path for the element
     *
     * @param string $partialPath
     * @return void
     */
    public function setPartialPath($partialPath)
    {
        $this->partialPath = (string)$partialPath;
    }

    /**
     * Returns TRUE if the element should be displayed
     *
     * @return bool
     */
    public function getShowElement()
    {
        return $this->showElement;
    }

    /**
     * TRUE if the element should be displayed
     *
     * @param bool $showElement
     * @return void
     */
    public function setShowElement($showElement = false)
    {
        $this->showElement = $showElement;
    }

    /**
     * Set the theme name
     *
     * @param string
     * @return $themeName
     */
    public function setThemeName($themeName = 'Default')
    {
        $this->themeName = $themeName;
    }

    /**
     * Returns the theme name
     *
     * @return string
     */
    public function getThemeName()
    {
        return $this->themeName;
    }
}
