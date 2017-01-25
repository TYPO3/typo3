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
use TYPO3\CMS\Form\Domain\Builder\FormBuilder;
use TYPO3\CMS\Form\Domain\Model\Element;

/**
 * Compatibility layer.
 * Used in the scope of one(!) specific form element.
 */
class CompatibilityLayerUtility
{
    /**
     * @param FormBuilder $formBuilder
     * @return CompatibilityLayerUtility
     */
    public static function create(FormBuilder $formBuilder)
    {
        /** @var CompatibilityLayerUtility $compatibilityService */
        $compatibilityService = \TYPO3\CMS\Form\Utility\FormUtility::getObjectManager()->get(self::class);
        $compatibilityService->setFormBuilder($formBuilder);
        return $compatibilityService;
    }

    /**
     * Layout array from form configuration
     *
     * @var array
     */
    protected $layout = [];

    /**
     * @var FormBuilder
     */
    protected $formBuilder;

    /**
     * @var array
     */
    protected $registeredFormElements = [
        'TEXTLINE',
        'SUBMIT',
        'RESET',
        'RADIO',
        'PASSWORD',
        'IMAGEBUTTON',
        'FILEUPLOAD',
        'CHECKBOX',
        'BUTTON',
        'TEXTAREA',
        'HIDDEN',
        'CONTENTELEMENT',
        'TEXTBLOCK',
        'SELECT',
        'FIELDSET',
        'RADIOGROUP',
        'CHECKBOXGROUP',
    ];

    /**
     * @var array
     */
    protected $elementsWithoutLabel = [
        'HIDDEN',
        'CONTENTELEMENT',
        'TEXTBLOCK',
        'FIELDSET',
        'RADIOGROUP',
        'CHECKBOXGROUP',
    ];

    /**
     * @var array
     */
    protected $containerElements = [
        'FIELDSET',
        'RADIOGROUP',
        'CHECKBOXGROUP',
    ];

    /**
     * @param FormBuilder $formBuilder
     */
    public function setFormBuilder(FormBuilder $formBuilder)
    {
        $this->formBuilder = $formBuilder;
    }

    /**
     * Set the layout configuration for one or more elements
     *
     * @param NULL|array $layout The configuration array
     * @return void
     * @deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8, as the functionality is now done via fluid
     */
    public function setGlobalLayoutConfiguration($layout = [])
    {
        GeneralUtility::deprecationLog('EXT:form: Do not use "layout." anymore. Deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8.');
        if (is_array($layout)) {
            foreach ($layout as $elementType => $elementValue) {
                $elementType = strtoupper($elementType);
                $this->layout[$elementType] = $elementValue;
            }
        }
    }

    /**
     * Get the layout of the object
     * Looks if there is an assigned layout by configuration of the element
     * otherwise it will look if there is a layout set in the form configuration.
     *
     * @param string $elementType Type of element e.g BUTTON
     * @return string The element layout
     * @deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8, as the functionality is now done via fluid
     */
    public function getGlobalLayoutByElementType($elementType)
    {
        GeneralUtility::deprecationLog('EXT:form: Do not use "layout." anymore. Deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8.');
        $layout = '';
        if (!empty($this->layout[$elementType])) {
            $layout = $this->layout[$elementType];
        } else {
            $action = $this->formBuilder->getControllerAction();
            switch ($elementType) {
                case 'FORM':
                    $layout = '<form><containerWrap /></form>';
                    break;
                case 'CONFIRMATION':
                    $layout = '<containerWrap />';
                    break;
                case 'HTML':
                    $layout = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body><table cellspacing="0"><containerWrap /></table></body></html>';
                    break;
                case 'CONTAINERWRAP':
                    if ($action !== 'process') {
                        $layout = '<ol><elements /></ol>';
                    } else {
                        $layout = '<tbody><elements /></tbody>';
                    }
                    break;
                case 'ELEMENTWRAP':
                    if ($action !== 'process') {
                        $layout = '<li><element /></li>';
                    } else {
                        $layout = '<tr><element /></tr>';
                    }
                    break;
                case 'LABEL':
                    if ($action === 'show') {
                        $layout = '<label><labelvalue /><mandatory /><error /></label>';
                    } elseif ($action === 'confirmation') {
                        $layout = '<label><labelvalue /></label>';
                    } else {
                        $layout = '<em><labelvalue /></em>';
                    }
                    break;
                case 'LEGEND':
                    if ($action === 'show') {
                        $layout = '<legend><legendvalue /><mandatory /><error /></legend>';
                    } elseif ($action === 'confirmation') {
                        $layout = '<legend><legendvalue /></legend>';
                    } else {
                        $layout = '<thead><tr><th colspan="2" align="left"><legendvalue /></th></tr></thead>';
                    }
                    break;
                case 'MANDATORY':
                    if ($action !== 'process') {
                        $layout = '<em><mandatoryvalue /></em>';
                    } else {
                        $layout = '';
                    }
                    break;
                case 'ERROR':
                    if ($action !== 'process') {
                        $layout = '<strong><errorvalue /></strong>';
                    } else {
                        $layout = '';
                    }
                    break;
                case 'RADIOGROUP':
                case 'CHECKBOXGROUP':
                case 'FIELDSET':
                    if ($action !== 'process') {
                        $layout = '<fieldset><legend /><containerWrap /></fieldset>';
                    } else {
                        $layout = '<td colspan="2"><table cellspacing="0" style="padding-left: 20px; margin-bottom: 20px;"><legend /><containerWrap /></table></td>';
                    }
                    break;
                case 'HIDDEN':
                    if ($action !== 'process') {
                        $layout = '<input />';
                    } else {
                        $layout = '';
                    }
                    break;
                case 'SELECT':
                    if ($action === 'show') {
                        $layout = '<label /><select><elements /></select>';
                    } elseif ($action === 'confirmation') {
                        $layout = '<label /><ol><elements /></ol>';
                    } else {
                        $layout = '<td style="width: 200px;"><label /></td><td><elements /></td>';
                    }
                    break;
                case 'TEXTAREA':
                    if ($action === 'show') {
                        $layout = '<label /><textarea />';
                    } elseif ($action === 'confirmation') {
                        $layout = '<label /><inputvalue />';
                    } else {
                        $layout = '<td style="width: 200px;" valign="top"><label /></td><td><inputvalue /></td>';
                    }
                    break;
                case 'BUTTON':
                case 'IMAGEBUTTON':
                case 'PASSWORD':
                case 'RESET':
                case 'SUBMIT':
                    if ($action !== 'show') {
                        $layout = '';
                        break;
                    }
                case 'CHECKBOX':
                case 'FILEUPLOAD':
                case 'RADIO':
                case 'TEXTLINE':
                    if ($action === 'show') {
                        $layout = '<label /><input />';
                    } elseif ($action === 'confirmation') {
                        $layout = '<label /><inputvalue />';
                    } else {
                        $layout = '<td style="width: 200px;"><label /></td><td><inputvalue /></td>';
                    }
                    break;
                case 'TEXTBLOCK':
                    if ($action === 'show') {
                        $layout = '<textblock />';
                    } else {
                        $layout = '';
                    }
                    break;
            }
        }
        return $layout;
    }

    /**
     * Set the layout for a element
     * Not supported / ignored: OPTGROUP, OPTION, layout.legend
     *
     * @param \TYPO3\CMS\Form\Domain\Model\Element $element
     * @param array $userConfiguredElementTyposcript The configuration array
     * @return void
     * @deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8, as the functionality is now done via fluid
     */
    public function setElementLayouts(Element $element, array $userConfiguredElementTyposcript = [])
    {
        GeneralUtility::deprecationLog('EXT:form: Do not use "layout." anymore. Deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8.');
        if ($element->getElementType() === 'FORM') {
            $containerWrapReturn = $this->replaceTagWithMarker('elements', 'body', $this->getGlobalLayoutByElementType('CONTAINERWRAP'));
            if ($this->formBuilder->getControllerAction() === 'show') {
                $formWrapReturn = $this->replaceTagWithMarker('containerwrap', 'form', $this->getGlobalLayoutByElementType('FORM'));
            } elseif ($this->formBuilder->getControllerAction() === 'confirmation') {
                $formWrapReturn = $this->replaceTagWithMarker('containerwrap', 'body', $this->getGlobalLayoutByElementType('CONFIRMATION'));
            } else {
                $formWrapReturn = $this->replaceTagWithMarker('containerwrap', 'html', $this->getGlobalLayoutByElementType('HTML'));
            }
            $formLayout = str_replace($formWrapReturn['marker'], $containerWrapReturn['html'], $formWrapReturn['html']);
            $formContainerWrap = explode($containerWrapReturn['marker'], $formLayout);
            $layout['containerInnerWrap'] = $formContainerWrap;
            $element->setLayout($layout);
            $classFromLayout = $this->getElementClassFromLayout('form');
            if (!empty($classFromLayout)) {
                if (!empty($element->getAdditionalArgument('class'))) {
                    $classFromLayout .= ' ' . $element->getAdditionalArgument('class');
                }
                $element->setAdditionalArgument('class', $classFromLayout);
            }
            return;
        }
        if (in_array($element->getElementType(), $this->registeredFormElements)) {
            /* Get the element layout definition or fallback to the global definition (if set) */
            if (isset($userConfiguredElementTyposcript['layout'])) {
                $elementLayout = $userConfiguredElementTyposcript['layout'];
            } else {
                $elementLayout = $this->getGlobalLayoutByElementType($element->getElementType());
            }
            /* if a element layout exist */
            $elementWrap = null;
            if ($elementLayout) {
                $elementWrap = $this->determineElementOuterWraps($element->getElementType(), $elementLayout);
                if ($elementWrap['html'] !== '') {
                    /* layout.label */
                    if (!in_array($element->getElementType(), $this->elementsWithoutLabel, true)) {
                        $descriptionElementLayouts = $this->getDescriptionElementLayouts($element);

                        if (!$descriptionElementLayouts['labelContainContent']) {
                            $descriptionElementLayouts['labelLayout'] = '';
                        } else {
                            $libxmlUseInternalErrors = libxml_use_internal_errors(true);
                            $dom = new \DOMDocument('1.0', 'utf-8');
                            $dom->formatOutput = true;
                            $dom->preserveWhiteSpace = false;
                            if ($dom->loadXML($descriptionElementLayouts['labelLayout'])) {
                                $nodes = $dom->getElementsByTagName('label');
                                if ($nodes->length) {
                                    $node = $nodes->item(0);
                                    if ($node) {
                                        $node->setAttribute('for', $element->getId());
                                        $descriptionElementLayouts['labelLayout'] = $dom->saveXML($dom->firstChild);
                                    }
                                }
                            }
                            libxml_use_internal_errors($libxmlUseInternalErrors);
                        }
                        /* Replace <label />, <error /> and <mandatory /> in the element wrap html */
                        $labelReturn = $this->replaceTagWithMarker('label', 'body', $elementWrap['html']);
                        if ($labelReturn['html'] !== '') {
                            $elementWrap['html'] = str_replace($labelReturn['marker'], $descriptionElementLayouts['labelLayout'], $labelReturn['html']);
                        }
                        $errorReturn = $this->replaceTagWithMarker('error', 'body', $elementWrap['html']);
                        if ($errorReturn['html'] !== '') {
                            $elementWrap['html'] = str_replace($errorReturn['marker'], $descriptionElementLayouts['errorLayout'], $errorReturn['html']);
                        }
                        $mandatoryReturn = $this->replaceTagWithMarker('mandatory', 'body', $elementWrap['html']);
                        if ($mandatoryReturn['html'] !== '') {
                            $elementWrap['html'] = str_replace($mandatoryReturn['marker'], $descriptionElementLayouts['mandatoryLayout'], $mandatoryReturn['html']);
                        }
                    }
                    $elementWrap = explode($elementWrap['marker'], $elementWrap['html']);
                } else {
                    $elementWrap = null;
                }
            }
            /* Set element outer wraps and set the default classes */
            $elementOuterWrap = null;
            if ($this->getGlobalLayoutByElementType('ELEMENTWRAP')) {
                $libxmlUseInternalErrors = libxml_use_internal_errors(true);
                $dom = new \DOMDocument('1.0', 'utf-8');
                $dom->formatOutput = true;
                $dom->preserveWhiteSpace = false;
                if ($dom->loadXML($this->getGlobalLayoutByElementType('ELEMENTWRAP'))) {
                    $node = $dom->firstChild;
                    if ($node) {
                        $class = '';
                        if ($node->getAttribute('class') !== '') {
                            $class = $node->getAttribute('class') . ' ';
                        }
                        $class .= 'csc-form-' . $element->getElementCounter() . ' csc-form-element csc-form-element-' . $element->getElementTypeLowerCase();
                        $node->setAttribute('class', $class);
                        $elementOuterWrap = $dom->saveXML($dom->firstChild);
                        $return = $this->replaceTagWithMarker('element', 'body', $elementOuterWrap);
                        if ($return['marker'] !== '') {
                            $elementOuterWrap = explode($return['marker'], $return['html']);
                            if ($element->getElementType() === 'SELECT') {
                                $layout = $element->getLayout();
                                $layout['optionOuterWrap'] = $elementOuterWrap;
                                $element->setLayout($layout);
                            }
                        } else {
                            /* this should never be happen */
                            $elementOuterWrap = null;
                        }
                    }
                } else {
                    $elementOuterWrap = null;
                }
                libxml_use_internal_errors($libxmlUseInternalErrors);
            }

            if (
                $elementWrap
                && !$elementOuterWrap
            ) {
                /* If only $elementWrap isset */
                $layout = $element->getLayout();
                $layout['elementOuterWrap'] = $elementWrap;
                $element->setLayout($layout);
            } elseif (
                !$elementWrap
                && $elementOuterWrap
            ) {
                /* If only $elementOuterWrap isset */
                $layout = $element->getLayout();
                $layout['elementOuterWrap'] = $elementOuterWrap;
                $element->setLayout($layout);
            } elseif (
                $elementWrap
                && $elementOuterWrap
            ) {
                /* If $elementWrap isset and $elementOuterWrap isset */
                $elementWrap = [
                    $elementOuterWrap[0] . $elementWrap[0],
                    $elementWrap[1] . $elementOuterWrap[1],
                ];
                $layout = $element->getLayout();
                $layout['elementOuterWrap'] = $elementWrap;
                $element->setLayout($layout);
            }

            /* Set container inner wraps */
            if (in_array($element->getElementType(), $this->containerElements)) {
                $elementWrap = $this->determineElementOuterWraps($element->getElementType(), $elementLayout);
                /* Replace the legend value */
                $descriptionElementLayouts = $this->getDescriptionElementLayouts($element, 'legend');
                if (!$descriptionElementLayouts['labelContainContent']) {
                    $descriptionElementLayouts['labelLayout'] = '';
                }
                /* No fieldset tag exist.
                 * Ignore CONTAINERWRAP
                 * */
                if ($elementWrap['html'] === '') {
                    $containerWrapReturn = $this->replaceTagWithMarker('elements', 'body', $elementLayout);
                    if ($containerWrapReturn['marker'] && $containerWrapReturn['html']) {
                        /* Replace <legend /> in the element wrap html */
                        $legendReturn = $this->replaceTagWithMarker('legend', 'body', $containerWrapReturn['html']);
                        if ($legendReturn['html'] !== '') {
                            $containerWrapReturn['html'] = str_replace($legendReturn['marker'], $descriptionElementLayouts['labelLayout'], $legendReturn['html']);
                        }
                        $containerWrap = explode($containerWrapReturn['marker'], $containerWrapReturn['html']);
                    } else {
                        $containerWrap = ['', ''];
                    }

                    $layout = $element->getLayout();
                    $layout['containerInnerWrap'] = $containerWrap;
                    $layout['noFieldsetTag'] = true;
                    $layout['legend'] = $descriptionElementLayouts['labelLayout'];
                    $element->setLayout($layout);
                } else {
                    /* set the wraps */
                    $containerOuterWrap = ['', ''];
                    $containerOuterWrap = explode($elementWrap['marker'], $elementWrap['html']);
                    $containerWrapReturn = $this->replaceTagWithMarker('elements', 'body', $this->getGlobalLayoutByElementType('CONTAINERWRAP'));
                    $containerInnerWrap = explode($containerWrapReturn['marker'], $containerWrapReturn['html']);

                    $containerWrap = [
                        $containerOuterWrap[0] . $containerInnerWrap[0],
                        $containerInnerWrap[1] . $containerOuterWrap[1],
                    ];

                    $layout = $element->getLayout();
                    $layout['containerInnerWrap'] = $containerWrap;
                    $layout['legend'] = $descriptionElementLayouts['labelLayout'];
                    $element->setLayout($layout);
                    $classFromLayout = $this->getElementClassFromLayout('fieldset');
                    if (!empty($classFromLayout)) {
                        if (!empty($element->getHtmlAttribute('class'))) {
                            $classFromLayout .= ' ' . $element->getHtmlAttribute('class');
                        }
                        $element->setHtmlAttribute('class', $classFromLayout);
                    }
                }
            } else {
                /* set class attribute for the element tag */
                if ($this->formBuilder->getControllerAction() === 'show') {
                    if ($elementType === 'TEXTAREA') {
                        $tagName = 'textarea';
                    } elseif ($elementType === 'SELECT') {
                        $tagName = 'select';
                    } else {
                        $tagName = 'input';
                    }

                    $classFromLayout = $this->getElementClassFromLayout($element->getElementType(), $tagName);
                    if (!empty($classFromLayout)) {
                        if (!empty($element->getAdditionalArgument('class'))) {
                            $classFromLayout .= ' ' . $element->getAdditionalArgument('class');
                        }
                        $element->setAdditionalArgument('class', $classFromLayout);
                    }
                }
            }
        }
    }

    /**
     * Replace the message sections of a label.
     * The scopes can be mandatory or error.
     *
     * @param string $scope
     * @param array $messages
     * @return string
     */
    protected function replaceLabelContent($scope = '', array $messages)
    {
        $messages = implode(' - ', $messages);
        $return = $this->replaceTagWithMarker($scope . 'value', 'body', $this->getGlobalLayoutByElementType(strtoupper($scope)));
        $html = str_replace($return['marker'], $messages, $return['html']);
        return $html;
    }

    /**
     * Replace <labelvalue /> or <legendvalue />, <mandatory />
     * and <error /> in a label / legend with the associated content.
     * Return the replaced <label /> / <legend /> layout and the replaced
     * <mandatory /> and <error /> layout.
     *
     * @param \TYPO3\CMS\Form\Domain\Model\Element $element
     * @param string $scope
     * @return array
     */
    protected function getDescriptionElementLayouts(Element $element, $scope = 'label')
    {
        $labelLayout = $this->getGlobalLayoutByElementType(strtoupper($scope));
        $labelContainContent = false;
        $mandatoryLayout = '';
        $errorLayout = '';
        if ($this->formBuilder->getControllerAction() === 'show') {
            /* Replace the mandatory and error messages */
            /* layout.mandatory */
            $mandatoryMessages = $this->formBuilder->getValidationBuilder()->getMandatoryValidationMessagesByElementName($element->getName());
            if (!empty($mandatoryMessages)) {
                $mandatoryLayout = $this->replaceLabelContent('mandatory', $mandatoryMessages);
            }
            $mandatoryReturn = $this->replaceTagWithMarker('mandatory', 'body', $labelLayout);
            if (!empty($mandatoryReturn['html'])) {
                if (!empty($mandatoryLayout)) {
                    $labelContainContent = true;
                }
                $labelLayout = str_replace($mandatoryReturn['marker'], $mandatoryLayout, $mandatoryReturn['html']);
            }

            /* layout.error */
            $errorMessages = $element->getValidationErrorMessages();
            if (!empty($errorMessages)) {
                $errorLayout = $this->replaceLabelContent('error', $errorMessages);
            }
            $errorReturn = $this->replaceTagWithMarker('error', 'body', $labelLayout);
            if (!empty($errorReturn['html'])) {
                if (!empty($errorLayout)) {
                    $labelContainContent = true;
                }
                $labelLayout = str_replace($errorReturn['marker'], $errorLayout, $errorReturn['html']);
            }
        }
        /* Replace the label value */
        $labelValueReturn = $this->replaceTagWithMarker($scope . 'value', 'body', $labelLayout);
        if (!empty($labelValueReturn['html'])) {
            if (!empty($element->getAdditionalArgument($scope))) {
                $labelContainContent = true;
            }
            $labelLayout = str_replace($labelValueReturn['marker'], $element->getAdditionalArgument($scope), $labelValueReturn['html']);
        }

        return [
            'labelContainContent' => $labelContainContent,
            'labelLayout' => $labelLayout,
            'errorLayout' => $errorLayout,
            'mandatoryLayout' => $mandatoryLayout
        ];
    }

    /**
     * Return the class attribute for a element defined by layout.
     *
     * @param string $elementType
     * @param string $tagName
     * @return string
     */
    protected function getElementClassFromLayout($elementType = '', $tagName = '')
    {
        $class = '';
        $libxmlUseInternalErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        if ($dom->loadXML($this->getGlobalLayoutByElementType(strtoupper($elementType)))) {
            if ($tagName === '') {
                $tagName = $elementType;
            }
            $nodes = $dom->getElementsByTagName($tagName);
            if ($nodes->length) {
                $node = $nodes->item(0);
                if ($node && $node->getAttribute('class') !== '') {
                    $class = $node->getAttribute('class');
                }
            }
        }
        libxml_use_internal_errors($libxmlUseInternalErrors);
        return $class;
    }

    /**
     * Try to explode the element layout into 2 parts to get the
     * outer wrapping
     *
     * @param string $elementType
     * @param string $elementLayout
     * @return string
     * @deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8, as the functionality is now done via fluid
     */
    protected function determineElementOuterWraps($elementType, $elementLayout = '')
    {
        if ($this->formBuilder->getControllerAction() === 'show') {
            if ($elementType === 'TEXTAREA') {
                $return = $this->replaceTagWithMarker('textarea', 'body', $elementLayout);
            } elseif ($elementType === 'CONTENTELEMENT') {
                $return = $this->replaceTagWithMarker('content', 'body', $elementLayout);
            } elseif ($elementType === 'SELECT') {
                $return = $this->replaceTagWithMarker('select', 'body', $elementLayout);
            } elseif (in_array($elementType, $this->containerElements)) {
                $return = $this->replaceTagWithMarker('fieldset', 'body', $elementLayout);
            } else {
                $return = $this->replaceTagWithMarker('input', 'body', $elementLayout);
            }
        } else {
            if ($elementType === 'CONTENTELEMENT') {
                $return = $this->replaceTagWithMarker('content', 'body', $elementLayout);
            } elseif ($elementType === 'SELECT') {
                $return = $this->replaceTagWithMarker('elements', 'body', $elementLayout);
            } elseif (in_array($elementType, $this->containerElements)) {
                if ($this->formBuilder->getControllerAction() === 'confirmation') {
                    $return = $this->replaceTagWithMarker('fieldset', 'body', $elementLayout);
                } else {
                    $return = $this->replaceTagWithMarker('containerwrap', 'body', $elementLayout);
                }
            } else {
                $return = $this->replaceTagWithMarker('inputvalue', 'body', $elementLayout);
            }
        }
        return $return;
    }

    /**
     * Replace a html tag with a unique marker
     *
     * @param string $tagName
     * @param string $stopTag
     * @param string $html
     * @return array
     */
    protected function replaceTagWithMarker($tagName, $stopTag = 'body', $html = '')
    {
        if (
            $tagName === ''
            || $html === ''
        ) {
            return [
                'html' => '',
                'marker' => ''
            ];
        }
        $libxmlUseInternalErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        if (!$dom->loadHTML('<?xml encoding="utf-8">' . $html)) {
            libxml_use_internal_errors($libxmlUseInternalErrors);
            return [
                'html' => '',
                'marker' => ''
            ];
        }
        libxml_use_internal_errors($libxmlUseInternalErrors);
        $nodes = $dom->getElementsByTagName($tagName);
        if (!$nodes->length) {
            return [
                'html' => '',
                'marker' => ''
            ];
        }
        $nodeToReplace = $nodes->item(0);
        /* Replace $tagname tag with a unique marker */
        $marker = '###' . uniqid() . '###';
        $markerNode = $dom->createTextNode($marker);
        $replaceNode = $dom->createDocumentFragment();
        $domNode = $dom->importNode($markerNode, true);
        $replaceNode->appendChild($domNode);
        $parentNode = $nodeToReplace->parentNode;
        $parentNode->insertBefore($replaceNode, $nodeToReplace);
        $parentNode->removeChild($nodeToReplace);
        $nextParent = $parentNode;
        /* Do not save the stop tag */
        while ($nextParent !== null) {
            if ($nextParent->tagName === $stopTag) {
                break;
            }
            $nextParent = $nextParent->parentNode;
        }
        $html = '';
        /* if stopTag == html, save the whole html */
        if ($stopTag === 'html') {
            $html = $nextParent->ownerDocument->saveHTML($nextParent);
        } else {
            /* do not save the stopTag */
            $children = $nextParent->childNodes;
            foreach ($children as $child) {
                $html .= $nextParent->ownerDocument->saveHTML($child);
            }
        }
        return [
            'html' => $html,
            'marker' => $marker
        ];
    }

    /**
     * Get new name for some old inconsistent attribute names
     *
     * @param string $elementType
     * @param string $attributeName
     * @return string
     * @deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8, as the functionality is now done via fluid
     */
    public function getNewAttributeName($elementType, $attributeName)
    {
        if ($elementType === 'OPTION') {
            if ($attributeName === 'data') {
                GeneralUtility::deprecationLog('EXT:form: Deprecated since TYPO3 CMS 7, use text instead of data to configure the OPTION text');
                $attributeName = 'text';
            }
        } elseif ($elementType === 'TEXTAREA') {
            if ($attributeName === 'data') {
                GeneralUtility::deprecationLog('EXT:form: Deprecated since TYPO3 CMS 7, use text instead of data to configure the TEXTAREA value');
                $attributeName = 'text';
            }
        } elseif ($elementType === 'TEXTBLOCK') {
            if ($attributeName === 'content') {
                GeneralUtility::deprecationLog('EXT:form: Deprecated since TYPO3 CMS 7, use text instead of content to configure the TEXTBLOCK value');
                $attributeName = 'text';
            }
        }
        return $attributeName;
    }
}
