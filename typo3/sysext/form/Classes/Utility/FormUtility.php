<?php
namespace TYPO3\CMS\Form\Utility;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Model\Configuration;

/**
 * A utility for the form
 */
class FormUtility
{
    /**
     * @param Configuration $configuration
     * @return FormBuilder
     */
    public static function create(Configuration $configuration)
    {
        /** @var FormBuilder $formBuilder */
        $formUtility = self::getObjectManager()->get(self::class);
        $formUtility->setConfiguration($configuration);
        return $formUtility;
    }

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Render TypoScript values
     * There are different variants. It is possible that the TypoScript
     * which we want to render looks like this:
     *
     * array(
     *   'message' => 'TEXT',
     *   'message.' => array(
     *     'value' => 'blah'
     *   )
     * )
     *
     * or in "short syntax" (the wizard writes some syntax partly)
     *
     * array(
     *   'message.' => array(
     *     'value' => 'blah'
     *   )
     * )
     *
     * or it is simply a string.
     *
     * Furthermore we have 2 modes:
     * - contentelement rendering is allowed
     * - or contentelement rendering is not allowed
     *
     * This method will take care of all scenarios and provide some
     * fallbacks.
     * Call this method always in the following way:
     *
     * renderItem(
     *   $typoscript['itemToRender.'],
     *   $typoscript['itemToRender'],
     *   $optionalDefaultMessage
     * )
     *
     * You dont have to handle if is $typoscript['itemToRender.'] is
     * set or not. This function determines this.
     * This allows us to get the value of a TypoScript construct
     * without knowing about "short syntax", only a string, a cObject,
     * if cObject rendering is allowed and so on.
     *
     * If contentelement rendering is allowed:
     *   If $type and $configuration are set
     *   render as an cObject.
     *
     *   If $type is set but $configuration is empty
     *   only return the value of $type.
     *
     *   If $type is empty and $configuration is an array ("short syntax")
     *   render the $configuration as content type TEXT.
     *
     *   If $type is empty and $configuration is a string
     *   render the value of $configuration like
     *   10 = TEXT 10.value = $configuration.
     *
     *   If $type is empty and $configuration is empty
     *   return the $defaultMessage.
     *
     * If contentelement rendering is not allowed:
     *   If $type is set but $configuration is empty
     *   only return the value of $type.
     *
     *   If $type is set and $configuration['value'] isset
     *   return the value of $configuration['value'].
     *
     *   If $type is set and $configuration['value'] is not set
     *   return the value of $defaultMessage.
     *
     *   If $type is empty and $configuration['value'] isset
     *   return the value of $configuration['value'].
     *
     *   If $type is empty and $configuration['value'] is not set
     *   return the value of $defaultMessage.
     *
     * @param mixed $configuration a string or a TypoScript array
     * @param NULL|string $type cObject type or simply a string value
     * @param string $defaultMessage
     * @return string
     */
    public function renderItem($configuration, $type = null, $defaultMessage = '')
    {
        if ($this->configuration->getContentElementRendering()) {
            $renderedMessage = null;
            if ($type !== null) {
                if (is_array($configuration)) {
                    /* Direct cObject rendering */
                    $value = $configuration;
                } else {
                    /* got only a string, no rendering required */
                    $renderedMessage = $type;
                }
            } else {
                if ($configuration !== null) {
                    /* Render the "short syntax"
                     * The wizard write things like label.value
                     * The previous version of EXT:form interpreted this
                     * as a TEXT content object, so we do the same
                     *  */
                    $type = 'TEXT';
                    if (is_array($configuration)) {
                        $value = $configuration;
                    } else {
                        $value['value'] = $configuration;
                    }
                } else {
                    /* return the default message
                     * If $type === NULL and $configuration === NULL
                     * return the default message (if set).
                     * */
                    $renderedMessage = $defaultMessage;
                }
            }
            if ($renderedMessage === null) {
                $renderedMessage = $GLOBALS['TSFE']->cObj->cObjGetSingle(
                    $type,
                    $value
                );
            }
        } else {
            if ($type !== null) {
                if ($configuration !== null) {
                    /* the wizard write things like label.value = some text
                     * so we need the handle that, even content object rendering
                     * is not allowed.
                     *  */
                    if (isset($configuration['value'])) {
                        $renderedMessage = $configuration['value'];
                    } else {
                        $renderedMessage = $defaultMessage;
                    }
                } else {
                    // string, no rendering required
                    $renderedMessage = $type;
                }
            } else {
                $renderedMessage = $defaultMessage;
                if (
                    is_array($configuration)
                    && isset($configuration['value'])
                ) {
                    $renderedMessage = $configuration['value'];
                }
            }
        }
        return $renderedMessage;
    }

    /**
     * Render array values recursively as cObjects using the
     * method renderItem.
     *
     * @param array $arrayToRender
     * @return array
     */
    public function renderArrayItems(array &$arrayToRender = [])
    {
        foreach ($arrayToRender as $attributeName => &$attributeValue) {
            $attributeNameWithoutDot = rtrim($attributeName, '.');
            if (
                isset($arrayToRender[$attributeNameWithoutDot])
                && isset($arrayToRender[$attributeNameWithoutDot . '.'])
            ) {
                $attributeValue = $this->renderItem(
                    $arrayToRender[$attributeNameWithoutDot . '.'],
                    $arrayToRender[$attributeNameWithoutDot]
                );
                unset($arrayToRender[$attributeNameWithoutDot . '.']);
            } elseif (
                !isset($arrayToRender[$attributeNameWithoutDot])
                && isset($arrayToRender[$attributeNameWithoutDot . '.'])
            ) {
                $this->renderArrayItems($attributeValue);
            }
        }
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
        if (!empty($name)) {
            // Change spaces into hyphens
            $name = preg_replace('/\\s/', '-', $name);
                // Remove non-word characters
            $name = preg_replace('/[^a-zA-Z0-9_\\-]+/', '', $name);
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
    public function sanitizeIdAttribute($id)
    {
        if (!empty($id)) {
            // Change spaces into hyphens
            $attribute = preg_replace('/\\s/', '-', $id);
            // Change first non-letter to field-
            if (preg_match('/^([^a-zA-Z]{1})/', $attribute)) {
                $id = 'field-' . $attribute;
            }
            // Remove non-word characters
            $id = preg_replace('/([^a-zA-Z0-9_:\\-\\.]*)/', '', $id);
        }
        return $id;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    public static function getObjectManager()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
    }
}
