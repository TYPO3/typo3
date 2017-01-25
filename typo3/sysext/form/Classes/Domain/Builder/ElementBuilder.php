<?php
namespace TYPO3\CMS\Form\Domain\Builder;

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

use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Form\Domain\Model\Element;

/**
 * Builder for Element domain models.
 */
class ElementBuilder
{
    /**
     * @param FormBuilder $formBuilder
     * @param Element $element
     * @param array $userDefinedTypoScript
     * @return ElementBuilder
     */
    public static function create(FormBuilder $formBuilder, Element $element, array $userDefinedTypoScript)
    {
        /** @var ElementBuilder $elementBuilder */
        $elementBuilder = \TYPO3\CMS\Form\Utility\FormUtility::getObjectManager()->get(self::class);
        $elementBuilder->setFormBuilder($formBuilder);
        $elementBuilder->setElement($element);
        $elementBuilder->setUserConfiguredElementTyposcript($userDefinedTypoScript);
        return $elementBuilder;
    }

    /**
     * @var \TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository
     */
    protected $typoScriptRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Service\TypoScriptService
     */
    protected $typoScriptService;

    /**
     * @var array
     */
    protected $userConfiguredElementTyposcript = [];

    /**
     * @var array
     */
    protected $htmlAttributes = [];

    /**
     * @var array
     */
    protected $additionalArguments = [];

    /**
     * @var array
     */
    protected $wildcardPrefixes = [];

    /**
     * @var FormBuilder
     */
    protected $formBuilder;

    /**
     * @var Element
     */
    protected $element;

    /**
     * @param \TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository $typoScriptRepository
     * @return void
     */
    public function injectTypoScriptRepository(\TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository $typoScriptRepository)
    {
        $this->typoScriptRepository = $typoScriptRepository;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\TypoScriptService $typoScriptService
     * @return void
     */
    public function injectTypoScriptService(\TYPO3\CMS\Extbase\Service\TypoScriptService $typoScriptService)
    {
        $this->typoScriptService = $typoScriptService;
    }

    /**
     * @param FormBuilder $formBuilder
     */
    public function setFormBuilder(FormBuilder $formBuilder)
    {
        $this->formBuilder = $formBuilder;
    }

    /**
     * @param Element $element
     */
    public function setElement(Element $element)
    {
        $this->element = $element;
    }

    /**
     * Set the fluid partial path to the element
     *
     * @return void
     */
    public function setPartialPaths()
    {
        $this->setElementPartialPath();
    }

    /**
     * Set the fluid partial path to the element
     *
     * @return void
     */
    protected function setElementPartialPath()
    {
        if (!isset($this->userConfiguredElementTyposcript['partialPath'])) {
            $partialPath = $this->typoScriptRepository->getDefaultFluidTemplate($this->element->getElementType());
        } else {
            $partialPath = $this->userConfiguredElementTyposcript['partialPath'];
            unset($this->userConfiguredElementTyposcript['partialPath']);
        }
        $this->element->setPartialPath($partialPath);
    }

    /**
     * Set the fluid partial path to the element
     *
     * @return void
     */
    public function setVisibility()
    {
        $visibility = false;
        if ($this->formBuilder->getControllerAction() === 'show') {
            if (!isset($this->userConfiguredElementTyposcript['visibleInShowAction'])) {
                $visibility = (bool)$this->typoScriptRepository->getModelConfigurationByScope($this->element->getElementType(), 'visibleInShowAction');
            } else {
                $visibility = (bool)$this->userConfiguredElementTyposcript['visibleInShowAction'];
            }
        } elseif ($this->formBuilder->getControllerAction() === 'confirmation') {
            if (!isset($this->userConfiguredElementTyposcript['visibleInConfirmationAction'])) {
                $visibility = (bool)$this->typoScriptRepository->getModelConfigurationByScope($this->element->getElementType(), 'visibleInConfirmationAction');
            } else {
                $visibility = (bool)$this->userConfiguredElementTyposcript['visibleInConfirmationAction'];
            }
        } elseif ($this->formBuilder->getControllerAction() === 'process') {
            if (!isset($this->userConfiguredElementTyposcript['visibleInMail'])) {
                $visibility = (bool)$this->typoScriptRepository->getModelConfigurationByScope($this->element->getElementType(), 'visibleInMail');
            } else {
                $visibility = (bool)$this->userConfiguredElementTyposcript['visibleInMail'];
            }
        }
        $this->element->setShowElement($visibility);
    }

    /**
     * Find all prefix-* attributes and return the
     * found prefixs. Than delete them from the htmlAttributes array
     *
     * @return void
     */
    public function setHtmlAttributeWildcards()
    {
        foreach ($this->htmlAttributes as $attributeName => $attributeValue) {
            if (strpos($attributeName, '-*') > 0) {
                $prefix = substr($attributeName, 0, -1);
                $this->wildcardPrefixes[] = $prefix;
                unset($this->htmlAttributes[$attributeName]);
            }
        }
    }

    /**
     * Overlay user defined html attribute values
     * To determine whats a html attribute, the htmlAttributes
     * array is used. If a html attribute value is found in userConfiguredElementTyposcript
     * this value is set to htmlAttributes and removed from userConfiguredElementTyposcript.
     *
     * @return void
     */
    public function overlayUserdefinedHtmlAttributeValues()
    {
        foreach ($this->htmlAttributes as $attributeName => $attributeValue) {
            $attributeNameWithoutDot = rtrim($attributeName, '.');
            $attributeNameToSet = $attributeNameWithoutDot;
            $rendered = false;
            /* If the attribute exists in the user configured typoscript */
            if ($this->arrayKeyExists($attributeName, $this->userConfiguredElementTyposcript)) {
                if ($this->formBuilder->getConfiguration()->getCompatibility()) {
                    $newAttributeName = $this->formBuilder->getCompatibilityService()->getNewAttributeName(
                        $this->element->getElementType(),
                        $attributeNameWithoutDot
                    );
                    /* Should the attribute be renamed? */
                    if ($newAttributeName !== $attributeNameWithoutDot) {
                        $attributeNameToSet = $newAttributeName;
                        /* If the renamed attribute already exists in the user configured typoscript */
                        if ($this->arrayKeyExists($newAttributeName, $this->userConfiguredElementTyposcript)) {
                            $attributeValue = $this->formBuilder->getFormUtility()->renderItem(
                                $this->userConfiguredElementTyposcript[$newAttributeName . '.'],
                                $this->userConfiguredElementTyposcript[$newAttributeName]
                            );
                            /* set renamed attribute name with the value of the renamed attribute */
                            $this->htmlAttributes[$newAttributeName] = $attributeValue;
                            /* unset the renamed attribute */
                            unset($this->userConfiguredElementTyposcript[$newAttributeName . '.']);
                            unset($this->userConfiguredElementTyposcript[$newAttributeName]);
                            $rendered = true;
                        }
                    }
                }
            }
            if ($rendered === false) {
                if ($this->arrayKeyExists($attributeNameWithoutDot, $this->userConfiguredElementTyposcript)) {
                    $attributeValue = $this->formBuilder->getFormUtility()->renderItem(
                        $this->userConfiguredElementTyposcript[$attributeNameWithoutDot . '.'],
                        $this->userConfiguredElementTyposcript[$attributeNameWithoutDot]
                    );
                    $this->htmlAttributes[$attributeNameToSet] = $attributeValue;
                }
            }
            unset($this->userConfiguredElementTyposcript[$attributeNameWithoutDot . '.']);
            unset($this->userConfiguredElementTyposcript[$attributeNameWithoutDot]);
        }

            // the prefix-* magic
        $ignoreKeys = [];
        foreach ($this->userConfiguredElementTyposcript as $attributeName => $attributeValue) {
            // ignore child elements
            if (
                MathUtility::canBeInterpretedAsInteger($attributeName)
                || isset($ignoreKeys[$attributeName])
            ) {
                $ignoreKeys[$attributeName . '.'] = true;
                continue;
            }

            foreach ($this->wildcardPrefixes as $wildcardPrefix) {
                if (strpos($attributeName, $wildcardPrefix) !== 0) {
                    continue;
                }
                $attributeNameWithoutDot = rtrim($attributeName, '.');
                $attributeValue = $this->formBuilder->getFormUtility()->renderItem(
                    $this->userConfiguredElementTyposcript[$attributeNameWithoutDot . '.'],
                    $this->userConfiguredElementTyposcript[$attributeNameWithoutDot]
                );
                $this->htmlAttributes[$attributeNameWithoutDot] = $attributeValue;
                $ignoreKeys[$attributeNameWithoutDot . '.'] = true;
                unset($this->userConfiguredElementTyposcript[$attributeNameWithoutDot . '.']);
                unset($this->userConfiguredElementTyposcript[$attributeNameWithoutDot]);
                break;
            }
        }
    }

    /**
     * If fixedHtmlAttributeValues are defined for this element
     * then overwrite the html attribute value
     *
     * @return void
     */
    public function overlayFixedHtmlAttributeValues()
    {
        $fixedHtmlAttributeValues = $this->typoScriptRepository->getModelConfigurationByScope($this->element->getElementType(), 'fixedHtmlAttributeValues.');
        if (is_array($fixedHtmlAttributeValues)) {
            foreach ($fixedHtmlAttributeValues as $attributeName => $attributeValue) {
                $this->htmlAttributes[$attributeName] = $attributeValue;
            }
        }
    }

    /**
     * Move htmlAttributes to additionalArguments that must be passed
     * as a view helper argument
     *
     * @return void
     */
    public function moveHtmlAttributesToAdditionalArguments()
    {
        $htmlAttributesUsedByTheViewHelperDirectly = $this->typoScriptRepository->getModelConfigurationByScope($this->element->getElementType(), 'htmlAttributesUsedByTheViewHelperDirectly.');
        if (is_array($htmlAttributesUsedByTheViewHelperDirectly)) {
            foreach ($htmlAttributesUsedByTheViewHelperDirectly as $attributeName) {
                if (array_key_exists($attributeName, $this->htmlAttributes)) {
                    $this->additionalArguments[$attributeName] = $this->htmlAttributes[$attributeName];
                    unset($this->htmlAttributes[$attributeName]);
                }
            }
        }
    }

    /**
     * Set the viewhelper default arguments in the additionalArguments array
     *
     * @return void
     */
    public function setViewHelperDefaulArgumentsToAdditionalArguments()
    {
        $viewHelperDefaultArguments = $this->typoScriptRepository->getModelConfigurationByScope($this->element->getElementType(), 'viewHelperDefaultArguments.');
        if (is_array($viewHelperDefaultArguments)) {
            foreach ($viewHelperDefaultArguments as $viewHelperDefaulArgumentName => $viewHelperDefaulArgumentValue) {
                $viewHelperDefaulArgumentNameWithoutDot = rtrim($viewHelperDefaulArgumentName, '.');
                $this->additionalArguments[$viewHelperDefaulArgumentNameWithoutDot] = $viewHelperDefaulArgumentValue;
            }
        }
        unset($this->userConfiguredElementTyposcript['viewHelperDefaultArguments']);
    }

    /**
     * Move all userdefined properties to the additionalArguments
     * array. Ignore the child elements
     *
     * @return void
     */
    public function moveAllOtherUserdefinedPropertiesToAdditionalArguments()
    {
        $viewHelperDefaultArguments = $this->typoScriptRepository->getModelConfigurationByScope($this->element->getElementType(), 'viewHelperDefaultArguments.');
        $ignoreKeys = [];

        foreach ($this->userConfiguredElementTyposcript as $attributeName => $attributeValue) {
            // ignore child elements
            if (
                MathUtility::canBeInterpretedAsInteger($attributeName)
                || isset($ignoreKeys[$attributeName])
                || $attributeName == 'postProcessor.'
                || $attributeName == 'rules.'
                || $attributeName == 'filters.'
                || $attributeName == 'layout'
            ) {
                $ignoreKeys[$attributeName . '.'] = true;
                continue;
            }
            $attributeNameWithoutDot = rtrim($attributeName, '.');
            $attributeNameToSet = $attributeNameWithoutDot;
            $rendered = false;
            if ($this->formBuilder->getConfiguration()->getCompatibility()) {
                $newAttributeName = $this->formBuilder->getCompatibilityService()->getNewAttributeName(
                    $this->element->getElementType(),
                    $attributeNameWithoutDot
                );
                /* Should the attribute be renamed? */
                if ($newAttributeName !== $attributeNameWithoutDot) {
                    $attributeNameToSet = $newAttributeName;
                    /* If the renamed attribute already exists in the user configured typoscript */
                    if ($this->arrayKeyExists($newAttributeName, $this->userConfiguredElementTyposcript)) {
                        $attributeValue = $this->formBuilder->getFormUtility()->renderItem(
                            $this->userConfiguredElementTyposcript[$newAttributeName . '.'],
                            $this->userConfiguredElementTyposcript[$newAttributeName]
                        );
                        /* set renamed attribute name with the value of the renamed attribute */
                        $this->additionalArguments[$newAttributeName] = $attributeValue;
                        /* unset the renamed attribute */
                        $ignoreKeys[$newAttributeName . '.'] = true;
                        $ignoreKeys[$newAttributeName] = true;
                        unset($this->userConfiguredElementTyposcript[$newAttributeName . '.']);
                        unset($this->userConfiguredElementTyposcript[$newAttributeName]);
                        $rendered = true;
                    }
                }
            }
            if ($rendered === false) {
                if (
                    isset($viewHelperDefaultArguments[$attributeName])
                    || isset($viewHelperDefaultArguments[$attributeNameWithoutDot])
                ) {
                    $this->formBuilder->getFormUtility()->renderArrayItems($attributeValue);
                    $attributeValue = $this->typoScriptService->convertTypoScriptArrayToPlainArray($attributeValue);
                } else {
                    $attributeValue = $this->formBuilder->getFormUtility()->renderItem(
                        $this->userConfiguredElementTyposcript[$attributeNameWithoutDot . '.'],
                        $this->userConfiguredElementTyposcript[$attributeNameWithoutDot]
                    );
                }
                $this->additionalArguments[$attributeNameToSet] = $attributeValue;
                $ignoreKeys[$attributeNameToSet . '.'] = true;
                $ignoreKeys[$attributeNameToSet] = true;
            }
            unset($this->userConfiguredElementTyposcript[$attributeNameWithoutDot . '.']);
            unset($this->userConfiguredElementTyposcript[$attributeNameWithoutDot]);
        }
            // remove "stdWrap." from "additionalArguments" on
            // the FORM Element
        if (
            !$this->formBuilder->getConfiguration()->getContentElementRendering()
            && $this->element->getElementType() == 'FORM'
        ) {
            unset($this->additionalArguments['stdWrap']);
            unset($this->additionalArguments['stdWrap.']);
        }
    }

    /**
     * Set the name and id attribute
     *
     * @return array
     */
    public function setNameAndId()
    {
        if (
            $this->element->getParentElement()
            && (int)$this->typoScriptRepository->getModelConfigurationByScope($this->element->getParentElement()->getElementType(), 'childrenInheritName') == 1
        ) {
            $this->htmlAttributes['name'] = $this->element->getParentElement()->getName();
            $this->additionalArguments['multiple'] = '1';
            $name = $this->sanitizeNameAttribute($this->userConfiguredElementTyposcript['name']);
            $this->element->setName($name);
        } else {
            $this->htmlAttributes['name'] = $this->sanitizeNameAttribute($this->htmlAttributes['name']);
            $this->element->setName($this->htmlAttributes['name']);
        }
        $this->htmlAttributes['id'] = $this->sanitizeIdAttribute($this->htmlAttributes['id']);
        $this->element->setId($this->htmlAttributes['id']);
    }

    /**
     * If the name is not defined it is automatically generated
     * using the following syntax: id-{element_counter}
     * The name attribute will be transformed if it contains some
     * non allowed characters:
     * - spaces are changed into hyphens
     * - remove all characters except a-z A-Z 0-9 _ -
     *
     * @param string $name
     * @return string
     */
    public function sanitizeNameAttribute($name)
    {
        $name = $this->formBuilder->getFormUtility()->sanitizeNameAttribute($name);
        if (empty($name)) {
            $name = 'id-' . $this->element->getElementCounter();
        }
        return $name;
    }

    /**
     * If the id is not defined it is automatically generated
     * using the following syntax: field-{element_counter}
     * The id attribute will be transformed if it contains some
     * non allowed characters:
     * - spaces are changed into hyphens
     * - if the id start with a integer then transform it to field-{integer}
     * - remove all characters expect a-z A-Z 0-9 _ - : .
     *
     * @param string $id
     * @return string
     */
    protected function sanitizeIdAttribute($id)
    {
        $id = $this->formBuilder->getFormUtility()->sanitizeIdAttribute($id);
        if (empty($id)) {
            $id = 'field-' . $this->element->getElementCounter();
        }
        return $id;
    }

    /**
     * Check if a needle exists in a array.
     *
     * @param string $needle
     * @param array $haystack
     * @return bool TRUE if found
     */
    protected function arrayKeyExists($needle, array $haystack = [])
    {
        return
            isset($haystack[$needle]) || isset($haystack[$needle . '.'])
        ;
    }

    /**
     * Get the current html attributes
     *
     * @return array
     */
    public function getHtmlAttributes()
    {
        return $this->htmlAttributes;
    }

    /**
     * Set the current html attributes
     *
     * @param array $htmlAttributes
     */
    public function setHtmlAttributes(array $htmlAttributes)
    {
        $this->htmlAttributes = $htmlAttributes;
    }

    /**
     * Get the current additional arguments
     *
     * @return array
     */
    public function getAdditionalArguments()
    {
        return $this->additionalArguments;
    }

    /**
     * Set the current additional arguments
     *
     * @param array $additionalArguments
     */
    public function setAdditionalArguments(array $additionalArguments)
    {
        $this->additionalArguments = $additionalArguments;
    }

    /**
     * Get the current wildcard prefixes
     *
     * @return array
     */
    public function getWildcardPrefixes()
    {
        return $this->wildcardPrefixes;
    }

    /**
     * Set the current wildcard prefixes
     *
     * @param array $wildcardPrefixes
     */
    public function setWildcardPrefixes(array $wildcardPrefixes)
    {
        $this->wildcardPrefixes = $wildcardPrefixes;
    }

    /**
     * Get the current Element
     *
     * @return array
     */
    public function getUserConfiguredElementTyposcript()
    {
        return $this->userConfiguredElementTyposcript;
    }

    /**
     * Set the current Element
     *
     * @param array $userConfiguredElementTyposcript
     */
    public function setUserConfiguredElementTyposcript(array $userConfiguredElementTyposcript)
    {
        $this->userConfiguredElementTyposcript = $userConfiguredElementTyposcript;
    }
}
