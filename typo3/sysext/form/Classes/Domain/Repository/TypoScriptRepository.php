<?php
namespace TYPO3\CMS\Form\Domain\Repository;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provide the TypoScript data-source
 */
class TypoScriptRepository implements SingletonInterface
{
    /**
     * @var array
     */
    protected $modelDefinitionTypoScript = [];

    /**
     * @var array
     */
    protected $registeredElementTypes = [];

    /**
     * @var \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
     */
    protected $typoScriptParser;

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->typoScriptParser = GeneralUtility::makeInstance(TypoScriptParser::class);
        $this->modelDefinitionTypoScript = $this->resolveTypoScriptReferences(
            $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_form.']
        );
        $this->setRegisteredElementTypes();
    }

    /**
     * Get all registered form elements
     *
     * @return array
     */
    public function getRegisteredElementTypes()
    {
        return $this->registeredElementTypes;
    }

    /**
     * Set all registered form elements
     *
     * @param array $registeredElementTypes
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setRegisteredElementTypes(array $registeredElementTypes = [])
    {
        if (!empty($registeredElementTypes)) {
            $this->registeredElementTypes = $registeredElementTypes;
        } else {
            if (!isset($this->modelDefinitionTypoScript['settings.']['registeredElements.'])) {
                throw new \InvalidArgumentException('There are no registeredElements available.', 1441791615);
            }
            $registeredElements = $this->modelDefinitionTypoScript['settings.']['registeredElements.'];
            foreach ($registeredElements as $registeredElementKey => $value) {
                $registeredElementKey = rtrim($registeredElementKey, '.');
                $this->registeredElementTypes[] = $registeredElementKey;
            }
        }
    }

    /**
     * Get the html attributes defined by the model
     * with their default values
     *
     * @param string $elementType
     * @return array
     */
    public function getModelDefinedHtmlAttributes($elementType = '')
    {
        if ($elementType == '') {
            return [];
        }
        $htmlAttributes = $this->getModelConfigurationByScope($elementType, 'htmlAttributes.');
        if (is_array($htmlAttributes)) {
            $htmlAttributes = array_fill_keys($htmlAttributes, null);
        } else {
            $htmlAttributes = [];
        }
        $defaultHtmlAttributeValues = $this->getModelConfigurationByScope($elementType, 'defaultHtmlAttributeValues.');
        if (is_array($defaultHtmlAttributeValues)) {
            foreach ($defaultHtmlAttributeValues as $defaultHtmlAttributeKey => $defaultHtmlAttributeValue) {
                $htmlAttributes[$defaultHtmlAttributeKey] = $defaultHtmlAttributeValue;
            }
        } elseif (!is_array($htmlAttributes)) {
            $htmlAttributes = [];
        }
        return $htmlAttributes;
    }

    /**
     * Get the default fluid template for a element.
     *
     * @param string $elementType
     * @param string $partialType
     * @return string
     */
    public function getDefaultFluidTemplate($elementType, $partialType = 'partialPath')
    {
        $partialPath = $this->getModelConfigurationByScope($elementType, $partialType);
        if ($partialPath) {
            return $partialPath;
        }
        return '';
    }

    /**
     * Get the model definition from TypoScript for a specific scope.
     *
     * @param string $elementType
     * @param string $scope
     * @return mixed
     */
    public function getModelConfigurationByScope($elementType, $scope)
    {
        if (isset($this->modelDefinitionTypoScript['settings.']['registeredElements.'][$elementType . '.'][$scope])) {
            return $this->modelDefinitionTypoScript['settings.']['registeredElements.'][$elementType . '.'][$scope];
        }
        return null;
    }

    /**
     * Get a registered class name by a
     * specific scope (validator or filter)
     *
     * @param string $name
     * @param string $scope (registeredValidators, registeredFilters)
     * @return mixed
     */
    public function getRegisteredClassName($name, $scope)
    {
        $name = strtolower($name);
        if (isset($this->modelDefinitionTypoScript['settings.'][$scope . '.'][$name . '.']['className'])) {
            return $this->modelDefinitionTypoScript['settings.'][$scope . '.'][$name . '.']['className'];
        }
        return null;
    }

    /**
     * Render a TypoScript and resolve all references (eg. " < plugin.tx_form...") recursively
     *
     * @param array $typoScript
     * @return array
     * @todo Extract to core then...
     */
    protected function resolveTypoScriptReferences(array $typoScript)
    {
        $ignoreKeys = [];
        foreach ($typoScript as $key => $value) {
            if (isset($ignoreKeys[$key])) {
                continue;
            }
            // i am a reference
            if ($value[0] === '<') {
                if (isset($typoScript[$key . '.'])) {
                    $oldTypoScript = $typoScript[$key . '.'];
                } else {
                    $oldTypoScript = [];
                }
                // detect search level
                $referencePath = trim(substr($value, 1));
                $dotPosition = strpos($referencePath, '.');
                if ($dotPosition === 0) {
                    // same position x =< .y
                    list($flatValue, $arrayValue) =  $this->typoScriptParser->getVal(substr($referencePath, 1), $typoScript);
                } else {
                    list($flatValue, $arrayValue) =  $this->typoScriptParser->getVal($referencePath, $GLOBALS['TSFE']->tmpl->setup);
                }
                if (is_array($arrayValue)) {
                    $typoScript[$key . '.'] = array_replace_recursive($arrayValue, $oldTypoScript);
                }
                if ($flatValue[0] === '<') {
                    $temporaryTypoScript = [
                        'temp' => $flatValue,
                        'temp.' => $typoScript[$key . '.'],
                    ];
                    $temporaryTypoScript = $this->resolveTypoScriptReferences($temporaryTypoScript);
                    $arrayValue = array_replace_recursive($temporaryTypoScript['temp.'], $arrayValue, $oldTypoScript);
                }
                if (is_array($arrayValue)) {
                    $typoScript[$key . '.'] = array_replace_recursive($arrayValue, $oldTypoScript);
                } elseif (isset($flatValue)) {
                    $typoScript[$key] = $flatValue;
                } else {
                    $typoScript[$key . '.'] = $oldTypoScript;
                }
            }
            // if array, then look deeper
            if (isset($typoScript[$key . '.'])) {
                $ignoreKeys[$key . '.'] = true;
                $typoScript[$key . '.'] = $this->resolveTypoScriptReferences($typoScript[$key . '.']);
            } elseif (is_array($typoScript[$key])) {
                // if array, then look deeper
                $typoScript[$key] = $this->resolveTypoScriptReferences($typoScript[$key]);
            }
        }
        return $typoScript;
    }
}
