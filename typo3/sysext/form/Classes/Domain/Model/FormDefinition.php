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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\Domain\Model;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Finishers\FinisherInterface;
use TYPO3\CMS\Form\Domain\Model\Exception\DuplicateFormElementException;
use TYPO3\CMS\Form\Domain\Model\Exception\FinisherPresetNotFoundException;
use TYPO3\CMS\Form\Domain\Model\Exception\FormDefinitionConsistencyException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractCompositeRenderable;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\VariableRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Exception as FormException;
use TYPO3\CMS\Form\Mvc\ProcessingRule;

/**
 * This class encapsulates a complete *Form Definition*, with all of its pages,
 * form elements, validation rules which apply and finishers which should be
 * executed when the form is completely filled in.
 *
 * It is *not modified* when the form executes.
 *
 * The Anatomy Of A Form
 * =====================
 *
 * A FormDefinition consists of multiple *Page* ({@link Page}) objects. When a
 * form is displayed to the user, only one *Page* is visible at any given time,
 * and there is a navigation to go back and forth between the pages.
 *
 * A *Page* consists of multiple *FormElements* ({@link FormElementInterface}, {@link AbstractFormElement}),
 * which represent the input fields, textareas, checkboxes shown inside the page.
 *
 * *FormDefinition*, *Page* and *FormElement* have *identifier* properties, which
 * must be unique for each given type (i.e. it is allowed that the FormDefinition and
 * a FormElement have the *same* identifier, but two FormElements are not allowed to
 * have the same identifier.
 *
 * Simple Example
 * --------------
 *
 * Generally, you can create a FormDefinition manually by just calling the API
 * methods on it, or you use a *Form Definition Factory* to build the form from
 * another representation format such as YAML.
 *
 * /---code php
 * $formDefinition = GeneralUtility::makeInstance(FormDefinition::class, 'myForm');
 *
 * $page1 = GeneralUtility::makeInstance(Page::class, 'page1');
 * $formDefinition->addPage($page);
 *
 * $element1 = GeneralUtility::makeInstance(GenericFormElement::class, 'title', 'Textfield'); # the second argument is the type of the form element
 * $page1->addElement($element1);
 * \---
 *
 * Creating a Form, Using Abstract Form Element Types
 * =====================================================
 *
 * While you can use the {@link FormDefinition::addPage} or {@link Page::addElement}
 * methods and create the Page and FormElement objects manually, it is often better
 * to use the corresponding create* methods ({@link FormDefinition::createPage}
 * and {@link Page::createElement}), as you pass them an abstract *Form Element Type*
 * such as *Text* or *Page*, and the system **automatically
 * resolves the implementation class name and sets default values**.
 *
 * So the simple example from above should be rewritten as follows:
 *
 * /---code php
 * $prototypeConfiguration = []; // We'll talk about this later
 *
 * $formDefinition = GeneralUtility::makeInstance(FormDefinition::class, 'myForm', $prototypeConfiguration);
 * $page1 = $formDefinition->createPage('page1');
 * $element1 = $page1->addElement('title', 'Textfield');
 * \---
 *
 * Now, you might wonder how the system knows that the element *Textfield*
 * is implemented using a GenericFormElement: **This is configured in the $prototypeConfiguration**.
 *
 * To make the example from above actually work, we need to add some sensible
 * values to *$prototypeConfiguration*:
 *
 * <pre>
 * $prototypeConfiguration = [
 *   'formElementsDefinition' => [
 *     'Page' => [
 *       'implementationClassName' => 'TYPO3\CMS\Form\Domain\Model\FormElements\Page'
 *     ],
 *     'Textfield' => [
 *       'implementationClassName' => 'TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement'
 *     ]
 *   ]
 * ]
 * </pre>
 *
 * For each abstract *Form Element Type* we add some configuration; in the above
 * case only the *implementation class name*. Still, it is possible to set defaults
 * for *all* configuration options of such an element, as the following example
 * shows:
 *
 * <pre>
 * $prototypeConfiguration = [
 *   'formElementsDefinition' => [
 *     'Page' => [
 *       'implementationClassName' => 'TYPO3\CMS\Form\Domain\Model\FormElements\Page',
 *       'label' => 'this is the label of the page if nothing is specified'
 *     ],
 *     'Textfield' => [
 *       'implementationClassName' => 'TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement',
 *       'label' = >'Default Label',
 *       'defaultValue' => 'Default form element value',
 *       'properties' => [
 *         'placeholder' => 'Text which is shown if element is empty'
 *       ]
 *     ]
 *   ]
 * ]
 * </pre>
 *
 * Using Preconfigured $prototypeConfiguration
 * ---------------------------------
 *
 * Often, it is not really useful to manually create the $prototypeConfiguration array.
 *
 * Most of it comes pre-configured inside the YAML settings of the extensions,
 * and the {@link \TYPO3\CMS\Form\Domain\Configuration\ConfigurationService} contains helper methods
 * which return the ready-to-use *$prototypeConfiguration*.
 *
 * Property Mapping and Validation Rules
 * =====================================
 *
 * Besides Pages and FormElements, the FormDefinition can contain information
 * about the *format of the data* which is inputted into the form. This generally means:
 *
 * - expected Data Types
 * - Property Mapping Configuration to be used
 * - Validation Rules which should apply
 *
 * Background Info
 * ---------------
 * You might wonder why Data Types and Validation Rules are *not attached
 * to each FormElement itself*.
 *
 * If the form should create a *hierarchical output structure* such as a multi-
 * dimensional array or a PHP object, your expected data structure might look as follows:
 * <pre>
 * - person
 * -- firstName
 * -- lastName
 * -- address
 * --- street
 * --- city
 * </pre>
 *
 * Now, let's imagine you want to edit *person.address.street* and *person.address.city*,
 * but want to validate that the *combination* of *street* and *city* is valid
 * according to some address database.
 *
 * In this case, the form elements would be configured to fill *street* and *city*,
 * but the *validator* needs to be attached to the *compound object* *address*,
 * as both parts need to be validated together.
 *
 * Connecting FormElements to the output data structure
 * ====================================================
 *
 * The *identifier* of the *FormElement* is most important, as it determines
 * where in the output structure the value which is entered by the user is placed,
 * and thus also determines which validation rules need to apply.
 *
 * Using the above example, if you want to create a FormElement for the *street*,
 * you should use the identifier *person.address.street*.
 *
 * Rendering a FormDefinition
 * ==========================
 *
 * In order to trigger *rendering* on a FormDefinition,
 * the current {@link \TYPO3\CMS\Extbase\Mvc\Request} needs to be bound to the FormDefinition,
 * resulting in a {@link \TYPO3\CMS\Form\Domain\Runtime\FormRuntime} object which contains the *Runtime State* of the form
 * (such as the currently inserted values).
 *
 * /---code php
 * # $currentRequest and $currentResponse need to be available, f.e. inside a controller you would
 * # use $this->request. Inside a ViewHelper you would use $this->renderingContext->getRequest()
 * $form = $formDefinition->bind($currentRequest);
 *
 * # now, you can use the $form object to get information about the currently
 * # entered values into the form, etc.
 * \---
 *
 * Refer to the {@link \TYPO3\CMS\Form\Domain\Runtime\FormRuntime} API doc for further information.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 *
 * @internal May change any time, use FormFactoryInterface to select a different FormDefinition if needed
 * @todo: Declare final in v12
 */
class FormDefinition extends AbstractCompositeRenderable implements VariableRenderableInterface
{

    /**
     * The Form's pages
     *
     * @var array<int, Page>
     */
    protected $renderables = [];

    /**
     * The finishers for this form
     *
     * @var list<FinisherInterface>
     */
    protected $finishers = [];

    /**
     * Property Mapping Rules, indexed by element identifier
     *
     * @var array<string, ProcessingRule>
     */
    protected $processingRules = [];

    /**
     * Contains all elements of the form, indexed by identifier.
     * Is used as internal cache as we need this really often.
     *
     * @var array<string, FormElementInterface>
     */
    protected $elementsByIdentifier = [];

    /**
     * Form element default values in the format ['elementIdentifier' => 'default value']
     *
     * @var array<string, mixed>
     */
    protected $elementDefaultValues = [];

    /**
     * Renderer class name to be used.
     *
     * @var string
     */
    protected $rendererClassName = '';

    /**
     * @var array<string, array<string, mixed>>
     */
    protected $typeDefinitions;

    /**
     * @var array<string, array<string, mixed>>
     */
    protected $validatorsDefinition;

    /**
     * @var array<string, array<string, mixed>>
     */
    protected $finishersDefinition;

    /**
     * The persistence identifier of the form
     *
     * @var string
     */
    protected $persistenceIdentifier;

    /**
     * Constructor. Creates a new FormDefinition with the given identifier.
     *
     * @param string $identifier The Form Definition's identifier, must be a non-empty string.
     * @param array $prototypeConfiguration overrides form defaults of this definition
     * @param string $type element type of this form
     * @param string|null $persistenceIdentifier the persistence identifier of the form
     * @throws IdentifierNotValidException if the identifier was not valid
     */
    public function __construct(
        string $identifier,
        array $prototypeConfiguration = [],
        string $type = 'Form',
        string $persistenceIdentifier = null
    ) {
        $this->typeDefinitions = $prototypeConfiguration['formElementsDefinition'] ?? [];
        $this->validatorsDefinition = $prototypeConfiguration['validatorsDefinition'] ?? [];
        $this->finishersDefinition = $prototypeConfiguration['finishersDefinition'] ?? [];

        if (!is_string($identifier) || strlen($identifier) === 0) {
            throw new IdentifierNotValidException('The given identifier was not a string or the string was empty.', 1477082503);
        }

        $this->identifier = $identifier;
        $this->type = $type;
        $this->persistenceIdentifier = (string)$persistenceIdentifier;

        if ($prototypeConfiguration !== []) {
            $this->initializeFromFormDefaults();
        }
    }

    /**
     * Initialize the form defaults of the current type
     *
     * @throws TypeDefinitionNotFoundException
     * @internal
     */
    protected function initializeFromFormDefaults()
    {
        if (!isset($this->typeDefinitions[$this->type])) {
            throw new TypeDefinitionNotFoundException(sprintf('Type "%s" not found. Probably some configuration is missing.', $this->type), 1474905835);
        }
        $typeDefinition = $this->typeDefinitions[$this->type];
        $this->setOptions($typeDefinition);
    }

    /**
     * Set multiple properties of this object at once.
     * Every property which has a corresponding set* method can be set using
     * the passed $options array.
     *
     * @param array $options
     * @param bool $resetFinishers
     * @internal
     */
    public function setOptions(array $options, bool $resetFinishers = false)
    {
        if (isset($options['rendererClassName'])) {
            $this->setRendererClassName($options['rendererClassName']);
        }
        if (isset($options['label'])) {
            $this->setLabel($options['label']);
        }
        if (isset($options['renderingOptions'])) {
            foreach ($options['renderingOptions'] as $key => $value) {
                $this->setRenderingOption($key, $value);
            }
        }
        if (isset($options['finishers'])) {
            if ($resetFinishers) {
                $this->finishers = [];
            }
            foreach ($options['finishers'] as $finisherConfiguration) {
                $this->createFinisher($finisherConfiguration['identifier'], $finisherConfiguration['options'] ?? []);
            }
        }

        if (isset($options['variants'])) {
            foreach ($options['variants'] as $variantConfiguration) {
                $this->createVariant($variantConfiguration);
            }
        }

        ArrayUtility::assertAllArrayKeysAreValid(
            $options,
            ['rendererClassName', 'renderingOptions', 'finishers', 'formEditor', 'label', 'variants']
        );
    }

    /**
     * Create a page with the given $identifier and attach this page to the form.
     *
     * - Create Page object based on the given $typeName
     * - set defaults inside the Page object
     * - attach Page object to this form
     * - return the newly created Page object
     *
     * @param string $identifier Identifier of the new page
     * @param string $typeName Type of the new page
     * @return Page the newly created page
     * @throws TypeDefinitionNotFoundException
     */
    public function createPage(string $identifier, string $typeName = 'Page'): Page
    {
        if (!isset($this->typeDefinitions[$typeName])) {
            throw new TypeDefinitionNotFoundException(sprintf('Type "%s" not found. Probably some configuration is missing.', $typeName), 1474905953);
        }

        $typeDefinition = $this->typeDefinitions[$typeName];

        if (!isset($typeDefinition['implementationClassName'])) {
            throw new TypeDefinitionNotFoundException(sprintf('The "implementationClassName" was not set in type definition "%s".', $typeName), 1477083126);
        }
        $implementationClassName = $typeDefinition['implementationClassName'];

        $classSchema = GeneralUtility::makeInstance(ReflectionService::class)->getClassSchema($implementationClassName);
        if ($classSchema->hasInjectMethods() || $classSchema->hasInjectProperties() || $classSchema->hasMethod('initializeObject')) {
            // @deprecated since v11, will be removed in v12 - Fallback for Page implementations that have
            // inject* or initializeObject methods, since Page prototype needs manual constructor arguments
            // which can't be mixed with injection in symfony DI. Deprecation is logged by ObjectManager->get().
            // Drop everything except the makeInstance call below in v12.
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @var Page $page */
            $page = $objectManager->get($implementationClassName, $identifier, $typeName);
        } else {
            /** @var Page $page */
            $page = GeneralUtility::makeInstance($implementationClassName, $identifier, $typeName);
        }

        if (isset($typeDefinition['label'])) {
            $page->setLabel($typeDefinition['label']);
        }

        if (isset($typeDefinition['renderingOptions'])) {
            foreach ($typeDefinition['renderingOptions'] as $key => $value) {
                $page->setRenderingOption($key, $value);
            }
        }

        if (isset($typeDefinition['variants'])) {
            foreach ($typeDefinition['variants'] as $variantConfiguration) {
                $page->createVariant($variantConfiguration);
            }
        }

        ArrayUtility::assertAllArrayKeysAreValid(
            $typeDefinition,
            ['implementationClassName', 'label', 'renderingOptions', 'formEditor', 'variants']
        );

        $this->addPage($page);
        return $page;
    }

    /**
     * Add a new page at the end of the form.
     *
     * Instead of this method, you should often use {@link createPage} instead.
     *
     * @param Page $page
     * @throws FormDefinitionConsistencyException if Page is already added to a FormDefinition
     * @see createPage
     */
    public function addPage(Page $page)
    {
        $this->addRenderable($page);
    }

    /**
     * Get the Form's pages
     *
     * @return array<int, Page> The Form's pages in the correct order
     */
    public function getPages(): array
    {
        return $this->renderables;
    }

    /**
     * Check whether a page with the given $index exists
     *
     * @param int $index
     * @return bool TRUE if a page with the given $index exists, otherwise FALSE
     */
    public function hasPageWithIndex(int $index): bool
    {
        return isset($this->renderables[$index]);
    }

    /**
     * Get the page with the passed index. The first page has index zero.
     *
     * If page at $index does not exist, an exception is thrown. @see hasPageWithIndex()
     *
     * @param int $index
     * @return Page the page
     * @throws FormException if the specified index does not exist
     */
    public function getPageByIndex(int $index)
    {
        if (!$this->hasPageWithIndex($index)) {
            throw new FormException(sprintf('There is no page with an index of %d', $index), 1329233627);
        }
        return $this->renderables[$index];
    }

    /**
     * Adds the specified finisher to this form
     *
     * @param FinisherInterface $finisher
     */
    public function addFinisher(FinisherInterface $finisher)
    {
        $this->finishers[] = $finisher;
    }

    /**
     * @param string $finisherIdentifier identifier of the finisher as registered in the current form (for example: "Redirect")
     * @param array $options options for this finisher in the format ['option1' => 'value1', 'option2' => 'value2', ...]
     * @return FinisherInterface
     * @throws FinisherPresetNotFoundException
     */
    public function createFinisher(string $finisherIdentifier, array $options = []): FinisherInterface
    {
        if (isset($this->finishersDefinition[$finisherIdentifier]) && is_array($this->finishersDefinition[$finisherIdentifier]) && isset($this->finishersDefinition[$finisherIdentifier]['implementationClassName'])) {
            $implementationClassName = $this->finishersDefinition[$finisherIdentifier]['implementationClassName'];
            $defaultOptions = $this->finishersDefinition[$finisherIdentifier]['options'] ?? [];
            ArrayUtility::mergeRecursiveWithOverrule($defaultOptions, $options);

            $classSchema = GeneralUtility::makeInstance(ReflectionService::class)->getClassSchema($implementationClassName);
            if (!$classSchema->hasMethod('setFinisherIdentifier')
                || ($classSchema->hasMethod('__construct')
                    && count($classSchema->getMethod('__construct')->getParameters()) >= 1
                    && (string)$classSchema->getMethod('__construct')->getFirstParameter()->getType() === 'string')
            ) {
                // @deprecated since v11, will be removed in v12 - Fallback for Finishers that do not
                // extend AbstractFinisher and have no setFinisherIdentifier() method or still have a
                // constructor argument to set the finisher name: Mixing manual constructor arguments
                // with injection is not allowed by symfony DI, so we dropped the constructor argument
                // for v11 to keep the injection.
                // The above if detects "old" finisher and falls back to ObjectManager, which will log
                // a deprecation.
                // Drop the if with this body in v12, keep else body, clean up
                // AbstractFinisher->setFinisherIdentifier() and enable the method in FinisherInterface.
                $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
                /** @var FinisherInterface $finisher */
                $finisher = $objectManager->get($implementationClassName, $finisherIdentifier);
                if ($classSchema->hasMethod('setFinisherIdentifier')) {
                    $finisher->setFinisherIdentifier($finisherIdentifier);
                }
            } else {
                /** @var FinisherInterface $finisher */
                $finisher = GeneralUtility::makeInstance($implementationClassName);
                $finisher->setFinisherIdentifier($finisherIdentifier);
            }

            $finisher->setOptions($defaultOptions);
            $this->addFinisher($finisher);
            return $finisher;
        }
        throw new FinisherPresetNotFoundException('The finisher preset identified by "' . $finisherIdentifier . '" could not be found, or the implementationClassName was not specified.', 1328709784);
    }

    /**
     * Gets all finishers of this form
     *
     * @return list<FinisherInterface>
     */
    public function getFinishers(): array
    {
        return $this->finishers;
    }

    /**
     * Add an element to the ElementsByIdentifier Cache.
     *
     * @param RenderableInterface $renderable
     * @throws DuplicateFormElementException
     * @internal
     */
    public function registerRenderable(RenderableInterface $renderable)
    {
        if ($renderable instanceof FormElementInterface) {
            if (isset($this->elementsByIdentifier[$renderable->getIdentifier()])) {
                throw new DuplicateFormElementException(sprintf('A form element with identifier "%s" is already part of the form.', $renderable->getIdentifier()), 1325663761);
            }
            $this->elementsByIdentifier[$renderable->getIdentifier()] = $renderable;
        }
    }

    /**
     * Remove an element from the ElementsByIdentifier cache
     *
     * @param RenderableInterface $renderable
     * @internal
     */
    public function unregisterRenderable(RenderableInterface $renderable)
    {
        if ($renderable instanceof FormElementInterface) {
            unset($this->elementsByIdentifier[$renderable->getIdentifier()]);
        }
    }

    /**
     * Get all form elements with their identifiers as keys
     *
     * @return array<string, FormElementInterface>
     */
    public function getElements(): array
    {
        return $this->elementsByIdentifier;
    }

    /**
     * Get a Form Element by its identifier
     *
     * If identifier does not exist, returns NULL.
     *
     * @param string $elementIdentifier
     * @return FormElementInterface|null The element with the given $elementIdentifier or NULL if none found
     */
    public function getElementByIdentifier(string $elementIdentifier)
    {
        return $this->elementsByIdentifier[$elementIdentifier] ?? null;
    }

    /**
     * Sets the default value of a form element
     *
     * @param string $elementIdentifier identifier of the form element. This supports property paths!
     * @param mixed $defaultValue
     * @internal
     */
    public function addElementDefaultValue(string $elementIdentifier, $defaultValue)
    {
        $this->elementDefaultValues = ArrayUtility::setValueByPath(
            $this->elementDefaultValues,
            $elementIdentifier,
            $defaultValue,
            '.'
        );
    }

    /**
     * returns the default value of the specified form element
     * or NULL if no default value was set
     *
     * @param string $elementIdentifier identifier of the form element. This supports property paths!
     * @return mixed The elements default value
     * @internal
     */
    public function getElementDefaultValueByIdentifier(string $elementIdentifier)
    {
        return ObjectAccess::getPropertyPath($this->elementDefaultValues, $elementIdentifier);
    }

    /**
     * Move $pageToMove before $referencePage
     *
     * @param Page $pageToMove
     * @param Page $referencePage
     */
    public function movePageBefore(Page $pageToMove, Page $referencePage)
    {
        $this->moveRenderableBefore($pageToMove, $referencePage);
    }

    /**
     * Move $pageToMove after $referencePage
     *
     * @param Page $pageToMove
     * @param Page $referencePage
     */
    public function movePageAfter(Page $pageToMove, Page $referencePage)
    {
        $this->moveRenderableAfter($pageToMove, $referencePage);
    }

    /**
     * Remove $pageToRemove from form
     *
     * @param Page $pageToRemove
     */
    public function removePage(Page $pageToRemove)
    {
        $this->removeRenderable($pageToRemove);
    }

    /**
     * Bind the current request & response to this form instance, effectively creating
     * a new "instance" of the Form.
     *
     * @param Request $request
     * @return FormRuntime
     */
    public function bind(Request $request): FormRuntime
    {
        $formRuntime = GeneralUtility::makeInstance(FormRuntime::class);
        $formRuntime->setFormDefinition($this);
        $formRuntime->setRequest($request);
        $formRuntime->initialize();
        return $formRuntime;
    }

    /**
     * @param string $propertyPath
     * @return ProcessingRule
     */
    public function getProcessingRule(string $propertyPath): ProcessingRule
    {
        if (!isset($this->processingRules[$propertyPath])) {
            $this->processingRules[$propertyPath] = GeneralUtility::makeInstance(ProcessingRule::class);
        }
        return $this->processingRules[$propertyPath];
    }

    /**
     * Get all mapping rules
     *
     * @return array<string, ProcessingRule>
     * @internal
     */
    public function getProcessingRules(): array
    {
        return $this->processingRules;
    }

    /**
     * @return array<string, array<string, mixed>>
     * @internal
     */
    public function getTypeDefinitions(): array
    {
        return $this->typeDefinitions;
    }

    /**
     * @return array<string, array<string, mixed>>
     * @internal
     */
    public function getValidatorsDefinition(): array
    {
        return $this->validatorsDefinition;
    }

    /**
     * Get the persistence identifier of the form
     *
     * @return string
     * @internal
     */
    public function getPersistenceIdentifier(): string
    {
        return $this->persistenceIdentifier;
    }

    /**
     * Set the renderer class name
     *
     * @param string $rendererClassName
     */
    public function setRendererClassName(string $rendererClassName)
    {
        $this->rendererClassName = $rendererClassName;
    }

    /**
     * Get the classname of the renderer
     *
     * @return string
     */
    public function getRendererClassName(): string
    {
        return $this->rendererClassName;
    }
}
