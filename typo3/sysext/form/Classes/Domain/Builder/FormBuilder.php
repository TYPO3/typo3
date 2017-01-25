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

use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Model\Configuration;
use TYPO3\CMS\Form\Domain\Model\Element;
use TYPO3\CMS\Form\Domain\Model\ValidationElement;
use TYPO3\CMS\Form\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Form\Utility\CompatibilityLayerUtility;
use TYPO3\CMS\Form\Utility\FormUtility;

/**
 * TypoScript factory for form
 *
 * Takes the incoming TypoScript and adds all the necessary form objects
 * according to the configuration.
 */
class FormBuilder
{
    /**
     * @var string
     */
    const COMPATIBILITY_THEME_NAME = 'Compatibility';

    /**
     * @param Configuration $configuration
     * @return FormBuilder
     */
    public static function create(Configuration $configuration)
    {
        /** @var FormBuilder $formBuilder */
        $formBuilder = \TYPO3\CMS\Form\Utility\FormUtility::getObjectManager()->get(self::class);
        $formBuilder->setConfiguration($configuration);
        return $formBuilder;
    }

    /**
     * @var FormUtility
     */
    protected $formUtility;

    /**
     * @var \TYPO3\CMS\Extbase\Service\TypoScriptService
     */
    protected $typoScriptService;

    /**
     * @var \TYPO3\CMS\Form\Utility\CompatibilityLayerUtility
     */
    protected $compatibilityService;

    /**
     * @var ValidationBuilder
     */
    protected $validationBuilder;

    /**
     * @var \TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository
     */
    protected $typoScriptRepository;

    /**
      * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
      */
    protected $signalSlotDispatcher;

    /**
     * @var \TYPO3\CMS\Form\Utility\SessionUtility
     */
    protected $sessionUtility;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Form\Utility\ElementCounter
     */
    protected $elementCounter;

    /**
     * @var NULL|\TYPO3\CMS\Extbase\Error\Result
     */
    protected $validationErrors = null;

    /**
     * @var Configuration;
     */
    protected $configuration;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @param \TYPO3\CMS\Extbase\Service\TypoScriptService $typoScriptService
     * @return void
     */
    public function injectTypoScriptService(\TYPO3\CMS\Extbase\Service\TypoScriptService $typoScriptService)
    {
        $this->typoScriptService = $typoScriptService;
    }

    /**
     * @param \TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository $typoScriptRepository
     * @return void
     */
    public function injectTypoScriptRepository(\TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository $typoScriptRepository)
    {
        $this->typoScriptRepository = $typoScriptRepository;
    }

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     * @return void
     */
    public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * @param \TYPO3\CMS\Form\Utility\SessionUtility $sessionUtility
     * @return void
     */
    public function injectSessionUtility(\TYPO3\CMS\Form\Utility\SessionUtility $sessionUtility)
    {
        $this->sessionUtility = $sessionUtility;
    }

    /**
     * @param \TYPO3\CMS\Form\Utility\ElementCounter $elementCounter
     * @return void
     */
    public function injectElementCounter(\TYPO3\CMS\Form\Utility\ElementCounter $elementCounter)
    {
        $this->elementCounter = $elementCounter;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     * @return void
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Creates this object.
     */
    public function __construct()
    {
        $this->compatibilityService = CompatibilityLayerUtility::create($this);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return ControllerContext
     */
    public function getControllerContext()
    {
        return $this->controllerContext;
    }

    /**
     * @param ControllerContext $controllerContext
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }

    /**
     * @return CompatibilityLayerUtility
     */
    public function getCompatibilityService()
    {
        return $this->compatibilityService;
    }

    /**
     * @param CompatibilityLayerUtility $compatibilityService
     */
    public function setCompatibilityService(CompatibilityLayerUtility $compatibilityService)
    {
        $this->compatibilityService = $compatibilityService;
    }

    /**
     * @return FormUtility
     */
    public function getFormUtility()
    {
        return $this->formUtility;
    }

    /**
     * @param FormUtility $formUtility
     */
    public function setFormUtility(FormUtility $formUtility)
    {
        $this->formUtility = $formUtility;
    }

    /**
     * @return ValidationBuilder
     */
    public function getValidationBuilder()
    {
        return $this->validationBuilder;
    }

    /**
     * @param ValidationBuilder $validationBuilder
     */
    public function setValidationBuilder(ValidationBuilder $validationBuilder)
    {
        $this->validationBuilder = $validationBuilder;
    }

    /**
     * Build model from TypoScript
     * Needed if more than one form exist at a page
     *
     * @return NULL|\TYPO3\CMS\Form\Domain\Model\Element The form object containing the child elements
     */
    public function buildModel()
    {
        $userConfiguredFormTypoScript = $this->configuration->getTypoScript();

        if ($this->configuration->getCompatibility()) {
            $layout = [];
            if (isset($userConfiguredFormTypoScript['layout.'])) {
                $layout = $userConfiguredFormTypoScript['layout.'];
                /* use the compatibility theme whenever if a layout is defined */
                $this->configuration->setThemeName(static::COMPATIBILITY_THEME_NAME);
                unset($userConfiguredFormTypoScript['layout.']);
            }

            switch ($this->getControllerAction()) {
                case 'show':
                    $actionLayoutKey = 'form.';
                    break;
                case 'confirmation':
                    $actionLayoutKey = 'confirmationView.';
                    break;
                case 'process':
                    $actionLayoutKey = 'postProcessor.';
                    break;
                default:
                    $actionLayoutKey = '';
                    break;
            }
            if ($actionLayoutKey && isset($userConfiguredFormTypoScript[$actionLayoutKey]['layout.'])) {
                $actionLayout = $userConfiguredFormTypoScript[$actionLayoutKey]['layout.'];
                $this->configuration->setThemeName(static::COMPATIBILITY_THEME_NAME);
                unset($userConfiguredFormTypoScript[$actionLayoutKey]['layout.']);
                $layout = array_replace_recursive($layout, $actionLayout);
            }

            if (!empty($layout)) {
                $this->compatibilityService->setGlobalLayoutConfiguration($layout);
            }
        }

        $form = $this->createElementObject();
        $this->reviveElement($form, $userConfiguredFormTypoScript, 'FORM');
        $form->setThemeName($this->configuration->getThemeName());
        return $form;
    }

    /**
     * Create a element
     *
     * @return \TYPO3\CMS\Form\Domain\Model\Element
     */
    protected function createElementObject()
    {
        $element = GeneralUtility::makeInstance(Element::class);
        return $element;
    }

    /**
     * Revive the domain model of the accordant element.
     *
     * @param Element $element
     * @param array $userConfiguredElementTypoScript The configuration array
     * @param string $elementType The element type (e.g BUTTON)
     * @return void
     */
    protected function reviveElement(Element $element, array $userConfiguredElementTypoScript, $elementType = '')
    {
        // @todo Check $userConfiguredElementTypoScript

        if ($elementType === 'IMAGEBUTTON') {
            GeneralUtility::deprecationLog('EXT:form: The element IMAGEBUTTON is deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8.');
        }

        $element->setElementType($elementType);
        $element->setElementCounter($this->elementCounter->getElementId());

        $elementBuilder = ElementBuilder::create($this, $element, $userConfiguredElementTypoScript);
        $elementBuilder->setPartialPaths();
        $elementBuilder->setVisibility();

        if ($element->getElementType() == 'CONTENTELEMENT') {
            $attributeValue = '';
            if ($this->configuration->getContentElementRendering()) {
                $attributeValue = $this->formUtility->renderItem(
                    $userConfiguredElementTypoScript['cObj.'],
                    $userConfiguredElementTypoScript['cObj']
                );
            }
            $element->setAdditionalArguments([
                'content' => $attributeValue,
            ]);
            /* use the compatibility theme whenever if a layout is defined */
            if ($this->configuration->getCompatibility()) {
                $this->compatibilityService->setElementLayouts($element, $userConfiguredElementTypoScript);
                if (isset($userConfiguredElementTypoScript['layout'])) {
                    $this->configuration->setThemeName(static::COMPATIBILITY_THEME_NAME);
                    unset($userConfiguredElementTypoScript['layout']);
                }
            }
        } else {
            $this->setAttributes($elementBuilder, $element, $userConfiguredElementTypoScript);
            $userConfiguredElementTypoScript = $elementBuilder->getUserConfiguredElementTypoScript();
            $this->setValidationMessages($element);
            /* use the compatibility theme whenever if a layout is defined */
            if ($this->configuration->getCompatibility()) {
                $this->compatibilityService->setElementLayouts($element, $userConfiguredElementTypoScript);
                if (isset($userConfiguredElementTypoScript['layout'])) {
                    $this->configuration->setThemeName(static::COMPATIBILITY_THEME_NAME);
                    unset($userConfiguredElementTypoScript['layout']);
                }
            }
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                'txFormAfterElementCreation',
                [$element, $this]
            );
                // create all child elements
            $this->setChildElementsByIntegerKey($element, $userConfiguredElementTypoScript);
        }
    }

    /**
     * Rendering of a "numerical array" of Form objects from TypoScript
     * Creates new object for each element found
     *
     * @param Element $element
     * @param array $userConfiguredElementTypoScript The configuration array
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function setChildElementsByIntegerKey(Element $element, array $userConfiguredElementTypoScript)
    {
        if (is_array($userConfiguredElementTypoScript)) {
            $keys = TemplateService::sortedKeyList($userConfiguredElementTypoScript);
            foreach ($keys as $key) {
                if (
                    (int)$key
                    && strpos($key, '.') === false
                ) {
                    $elementType = $userConfiguredElementTypoScript[$key];
                    if (isset($userConfiguredElementTypoScript[$key . '.'])) {
                        $concreteChildElementTypoScript = $userConfiguredElementTypoScript[$key . '.'];
                    } else {
                        $concreteChildElementTypoScript = [];
                    }
                    $this->distinguishElementType($element, $concreteChildElementTypoScript, $elementType);
                }
            }
        } else {
            throw new \InvalidArgumentException('Container element with id=' . $element->getElementCounter() . ' has no configuration which means no children.', 1333754854);
        }
    }

    /**
     * Create and add element by type.
     * If its not a registered form element
     * try to render it as contentelement with the internal elementType
     * CONTENTELEMENT
     *
     * @param Element $element
     * @param array $userConfiguredElementTypoScript The configuration array
     * @param string $elementType The element type (e.g BUTTON)
     * @return void
     */
    protected function distinguishElementType(Element $element, array $userConfiguredElementTypoScript, $elementType = '')
    {
        if (in_array($elementType, $this->typoScriptRepository->getRegisteredElementTypes())) {
            $this->addChildElement($element, $userConfiguredElementTypoScript, $elementType);
        } elseif ($this->configuration->getContentElementRendering()) {
            $contentObject = [
                'cObj' => $elementType,
                'cObj.' => $userConfiguredElementTypoScript
            ];
            $this->addChildElement($element, $contentObject, 'CONTENTELEMENT');
        }
    }

    /**
     * Add child object to this element
     *
     * @param Element $element
     * @param array $userConfiguredElementTypoScript The configuration array
     * @param string $elementType The element type (e.g BUTTON)
     * @return void
     */
    protected function addChildElement(Element $element, array $userConfiguredElementTypoScript, $elementType = '')
    {
        $childElement = $this->createElementObject();
        $childElement->setParentElement($element);
        $element->addChildElement($childElement);
        $this->reviveElement($childElement, $userConfiguredElementTypoScript, $elementType);
    }

    /**
     * Set the htmlAttributes and the additionalAttributes
     * Remap htmlAttributes to additionalAttributes if needed
     *
     * @param ElementBuilder $elementBuilder
     * @param Element $element
     * @return void
     */
    protected function setAttributes(ElementBuilder $elementBuilder, Element $element)
    {
        $htmlAttributes = $this->typoScriptRepository->getModelDefinedHtmlAttributes($element->getElementType());
        $elementBuilder->setHtmlAttributes($htmlAttributes);
        $elementBuilder->setHtmlAttributeWildcards();
        $elementBuilder->overlayUserdefinedHtmlAttributeValues();
        $elementBuilder->setNameAndId();
        $elementBuilder->overlayFixedHtmlAttributeValues();
        // remove all NULL values
        $htmlAttributes = array_filter($elementBuilder->getHtmlAttributes());

        $elementBuilder->setHtmlAttributes($htmlAttributes);
        $elementBuilder->moveHtmlAttributesToAdditionalArguments();
        $elementBuilder->setViewHelperDefaulArgumentsToAdditionalArguments();
        $elementBuilder->moveAllOtherUserdefinedPropertiesToAdditionalArguments();
        $htmlAttributes = $elementBuilder->getHtmlAttributes();
        $userConfiguredElementTypoScript = $elementBuilder->getUserConfiguredElementTypoScript();
        $additionalArguments = $elementBuilder->getAdditionalArguments();
        $element->setHtmlAttributes($htmlAttributes);
        $additionalArguments = $this->typoScriptService->convertTypoScriptArrayToPlainArray($additionalArguments);
        $additionalArguments['prefix'] = $this->configuration->getPrefix();
        $element->setAdditionalArguments($additionalArguments);
        $this->handleIncomingValues($element, $userConfiguredElementTypoScript);

        if (
            $element->getElementType() === 'FORM'
            && $this->getControllerAction() === 'show'
        ) {
            if (empty($element->getHtmlAttribute('action'))) {
                if (
                    $element->getAdditionalArgument('confirmation')
                    && (int)$element->getAdditionalArgument('confirmation') === 1
                ) {
                    $element->setAdditionalArgument('action', 'confirmation');
                } else {
                    $element->setAdditionalArgument('action', 'process');
                }
            } else {
                $element->setAdditionalArgument('pageUid', $element->getHtmlAttribute('action'));
                $element->setAdditionalArgument('action', null);
            }
        }

        // needed if confirmation page is enabled
        if (
            $this->sessionUtility->getSessionData($element->getName())
            && $element->getAdditionalArgument('uploadedFiles') === null
        ) {
            $element->setAdditionalArgument('uploadedFiles', $this->sessionUtility->getSessionData($element->getName()));
        }
    }

    /**
     * Handles the incoming form data
     *
     * @param Element $element
     * @param array $userConfiguredElementTypoScript
     * @return array
     */
    protected function handleIncomingValues(Element $element, array $userConfiguredElementTypoScript)
    {
        if (!$this->getIncomingData()) {
            return;
        }
        $elementName = $element->getName();
        if ($element->getHtmlAttribute('value') !== null) {
            $modelValue = $element->getHtmlAttribute('value');
        } else {
            $modelValue = $element->getAdditionalArgument('value');
        }

        if ($this->getIncomingData()->getIncomingField($elementName) !== null) {
            /* filter values and set it back to incoming fields */
                /* remove xss every time */
            $userConfiguredElementTypoScript['filters.'][-1] = 'removexss';
            $keys = TemplateService::sortedKeyList($userConfiguredElementTypoScript['filters.']);
            foreach ($keys as $key) {
                $class = $userConfiguredElementTypoScript['filters.'][$key];
                if (
                    (int)$key
                    && strpos($key, '.') === false
                ) {
                    $filterArguments = $userConfiguredElementTypoScript['filters.'][$key . '.'];
                    $filterClassName = $this->typoScriptRepository->getRegisteredClassName((string)$class, 'registeredFilters');
                    if ($filterClassName !== null) {
                        // toDo: handel array values
                        if (is_string($this->getIncomingData()->getIncomingField($elementName))) {
                            if (is_null($filterArguments)) {
                                $filter = $this->objectManager->get($filterClassName);
                            } else {
                                $filter = $this->objectManager->get($filterClassName, $filterArguments);
                            }
                            if ($filter) {
                                $value = $filter->filter($this->getIncomingData()->getIncomingField($elementName));
                                $this->getIncomingData()->setIncomingField($elementName, $value);
                            } else {
                                throw new \RuntimeException('Class "' . $filterClassName . '" could not be loaded.');
                            }
                        }
                    } else {
                        throw new \RuntimeException('Class "' . $filterClassName . '" not registered via TypoScript.');
                    }
                }
            }

            if ($element->getHtmlAttribute('value') !== null) {
                $element->setHtmlAttribute('value', $this->getIncomingData()->getIncomingField($elementName));
            } else {
                $element->setAdditionalArgument('value', $this->getIncomingData()->getIncomingField($elementName));
            }
        }
        $this->signalSlotDispatcher->dispatch(
            __CLASS__,
            'txFormHandleIncomingValues',
            [
                $element,
                $this->getIncomingData(),
                $modelValue,
                $this
            ]
        );
    }

    /**
     * Set the rendered mandatory message
     * and the validation error message if available
     *
     * @param Element $element
     * @return void
     */
    protected function setValidationMessages(Element $element)
    {
        $elementName = $element->getName();
        $mandatoryMessages = $this->validationBuilder->getMandatoryValidationMessagesByElementName($elementName);
        $element->setMandatoryValidationMessages($mandatoryMessages);
        if (
            $this->getValidationErrors()
            && $this->getValidationErrors()->forProperty($elementName)->hasErrors()
        ) {
            /** @var \TYPO3\CMS\Extbase\Error\Error[] $errors */
            $errors = $this->getValidationErrors()->forProperty($elementName)->getErrors();
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $element->setValidationErrorMessages($errorMessages);
        }
    }

    /**
     * Return the form prefix
     *
     * @return string
     */
    public function getFormPrefix()
    {
        return $this->configuration->getPrefix();
    }

    /**
     * TRUE if the content element rendering should be disabled.
     *
     * @return bool
     */
    public function getDisableContentElementRendering()
    {
        return !$this->configuration->getContentElementRendering();
    }

    /**
     * TRUE if the content element rendering should be disabled.
     *
     * @return string
     */
    public function getControllerAction()
    {
        return $this->controllerContext->getRequest()->getControllerActionName();
    }

    /**
     * If TRUE form try to respect the layout settings
     *
     * @return bool
     */
    public function getCompatibilityMode()
    {
        return $this->configuration->getCompatibility();
    }

    /**
     * Get the incoming flat form data
     *
     * @return ValidationElement
     */
    public function getIncomingData()
    {
        return $this->controllerContext->getValidationElement();
    }

    /**
     * Set the validation errors
     *
     * @param \TYPO3\CMS\Extbase\Error\Result $validationErrors
     * @return void
     */
    public function setValidationErrors(\TYPO3\CMS\Extbase\Error\Result $validationErrors)
    {
        $this->validationErrors = $validationErrors;
    }

    /**
     * Get the validation errors
     *
     * @return NULL|\TYPO3\CMS\Extbase\Error\Result
     */
    public function getValidationErrors()
    {
        return $this->validationErrors;
    }
}
