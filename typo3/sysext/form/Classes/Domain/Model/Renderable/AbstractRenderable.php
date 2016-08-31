<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Model\Renderable;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Form\Domain\Model\Exception\FormDefinitionConsistencyException;
use TYPO3\CMS\Form\Domain\Model\Exception\ValidatorPresetNotFoundException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Convenience base class which implements common functionality for most
 * classes which implement RenderableInterface.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 * @internal
 */
abstract class AbstractRenderable implements RenderableInterface
{

    /**
     * Abstract "type" of this Renderable. Is used during the rendering process
     * to determine the template file or the View PHP class being used to render
     * the particular element.
     *
     * @var string
     */
    protected $type;

    /**
     * The identifier of this renderable
     *
     * @var string
     */
    protected $identifier;

    /**
     * The parent renderable
     *
     * @var CompositeRenderableInterface
     */
    protected $parentRenderable;

    /**
     * The label of this renderable
     *
     * @var string
     */
    protected $label = '';

    /**
     * associative array of rendering options
     *
     * @var array
     */
    protected $renderingOptions = [];

    /**
     * Renderer class name to be used for this renderable.
     *
     * Is only set if a specific renderer should be used for this renderable,
     * if it is NULL the caller needs to determine the renderer or take care
     * of the rendering itself.
     *
     * @var string
     */
    protected $rendererClassName = null;

    /**
     * The position of this renderable inside the parent renderable.
     *
     * @var int
     */
    protected $index = 0;

    /**
     * Get the type of the renderable
     *
     * @return string
     * @api
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the identifier of the element
     *
     * @return string
     * @api
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Set multiple properties of this object at once.
     * Every property which has a corresponding set* method can be set using
     * the passed $options array.
     *
     * @param array $options
     * @return void
     * @api
     */
    public function setOptions(array $options)
    {
        if (isset($options['label'])) {
            $this->setLabel($options['label']);
        }

        if (isset($options['defaultValue'])) {
            $this->setDefaultValue($options['defaultValue']);
        }

        if (isset($options['properties'])) {
            foreach ($options['properties'] as $key => $value) {
                $this->setProperty($key, $value);
            }
        }

        if (isset($options['rendererClassName'])) {
            $this->setRendererClassName($options['rendererClassName']);
        }

        if (isset($options['renderingOptions'])) {
            foreach ($options['renderingOptions'] as $key => $value) {
                if (is_array($value)) {
                    $currentValue = isset($this->getRenderingOptions()[$key]) ? $this->getRenderingOptions()[$key] : [];
                    ArrayUtility::mergeRecursiveWithOverrule($currentValue, $value);
                    $this->setRenderingOption($key, $currentValue);
                } else {
                    $this->setRenderingOption($key, $value);
                }
            }
        }

        if (isset($options['validators'])) {
            foreach ($options['validators'] as $validatorConfiguration) {
                $this->createValidator($validatorConfiguration['identifier'], isset($validatorConfiguration['options']) ? $validatorConfiguration['options'] : []);
            }
        }

        ArrayUtility::assertAllArrayKeysAreValid(
            $options,
            ['label', 'defaultValue', 'properties', 'rendererClassName', 'renderingOptions', 'validators', 'formEditor']
        );
    }

    /**
     * Create a validator for the element
     *
     * @param string $validatorIdentifier
     * @param array $options
     * @return mixed
     * @throws ValidatorPresetNotFoundException
     * @api
     */
    public function createValidator(string $validatorIdentifier, array $options = [])
    {
        $validatorsDefinition = $this->getRootForm()->getValidatorsDefinition();
        if (isset($validatorsDefinition[$validatorIdentifier]) && is_array($validatorsDefinition[$validatorIdentifier]) && isset($validatorsDefinition[$validatorIdentifier]['implementationClassName'])) {
            $implementationClassName = $validatorsDefinition[$validatorIdentifier]['implementationClassName'];
            $defaultOptions = isset($validatorsDefinition[$validatorIdentifier]['options']) ? $validatorsDefinition[$validatorIdentifier]['options'] : [];

            ArrayUtility::mergeRecursiveWithOverrule($defaultOptions, $options);

            $validator = GeneralUtility::makeInstance(ObjectManager::class)
                ->get($implementationClassName, $defaultOptions);
            $this->addValidator($validator);
            return $validator;
        } else {
            throw new ValidatorPresetNotFoundException('The validator preset identified by "' . $validatorIdentifier . '" could not be found, or the implementationClassName was not specified.', 1328710202);
        }
    }

    /**
     * Add a validator to the element
     *
     * @param ValidatorInterface $validator
     * @return void
     * @api
     */
    public function addValidator(ValidatorInterface $validator)
    {
        $formDefinition = $this->getRootForm();
        $formDefinition->getProcessingRule($this->getIdentifier())->addValidator($validator);
    }

    /**
     * Get all validators on the element
     *
     * @return \SplObjectStorage
     * @internal
     */
    public function getValidators(): \SplObjectStorage
    {
        $formDefinition = $this->getRootForm();
        return $formDefinition->getProcessingRule($this->getIdentifier())->getValidators();
    }

    /**
     * Set the datatype
     *
     * @param string $dataType
     * @return void
     * @api
     */
    public function setDataType(string $dataType)
    {
        $formDefinition = $this->getRootForm();
        $formDefinition->getProcessingRule($this->getIdentifier())->setDataType($dataType);
    }

    /**
     * Set the renderer class name
     *
     * @param string $rendererClassName
     * @return void
     * @api
     */
    public function setRendererClassName(string $rendererClassName)
    {
        $this->rendererClassName = $rendererClassName;
    }

    /**
     * Get the classname of the renderer
     *
     * @return null|string
     * @api
     */
    public function getRendererClassName()
    {
        return $this->rendererClassName;
    }

    /**
     * Get all rendering options
     *
     * @return array
     * @api
     */
    public function getRenderingOptions(): array
    {
        return $this->renderingOptions;
    }

    /**
     * Set the rendering option $key to $value.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     * @api
     */
    public function setRenderingOption(string $key, $value)
    {
        $this->renderingOptions[$key] = $value;
    }

    /**
     * Get the parent renderable
     *
     * @return null|CompositeRenderableInterface
     * @return void
     * @api
     */
    public function getParentRenderable()
    {
        return $this->parentRenderable;
    }

    /**
     * Set the parent renderable
     *
     * @param CompositeRenderableInterface $parentRenderable
     * @return void
     * @api
     */
    public function setParentRenderable(CompositeRenderableInterface $parentRenderable)
    {
        $this->parentRenderable = $parentRenderable;
        $this->registerInFormIfPossible();
    }

    /**
     * Get the root form this element belongs to
     *
     * @return FormDefinition
     * @throws FormDefinitionConsistencyException
     * @api
     */
    public function getRootForm(): FormDefinition
    {
        $rootRenderable = $this->parentRenderable;
        while ($rootRenderable !== null && !($rootRenderable instanceof FormDefinition)) {
            $rootRenderable = $rootRenderable->getParentRenderable();
        }
        if ($rootRenderable === null) {
            throw new FormDefinitionConsistencyException(sprintf('The form element "%s" is not attached to a parent form.', $this->identifier), 1326803398);
        }

        return $rootRenderable;
    }

    /**
     * Register this element at the parent form, if there is a connection to the parent form.
     *
     * @return void
     * @internal
     */
    public function registerInFormIfPossible()
    {
        try {
            $rootForm = $this->getRootForm();
            $rootForm->registerRenderable($this);
        } catch (FormDefinitionConsistencyException $exception) {
        }
    }

    /**
     * Triggered when the renderable is removed from it's parent
     *
     * @return void
     * @internal
     */
    public function onRemoveFromParentRenderable()
    {
        try {
            $rootForm = $this->getRootForm();
            $rootForm->unregisterRenderable($this);
        } catch (FormDefinitionConsistencyException $exception) {
        }
        $this->parentRenderable = null;
    }

    /**
     * Get the index of the renderable
     *
     * @return int
     * @internal
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * Set the index of the renderable
     *
     * @param int $index
     * @return void
     * @internal
     */
    public function setIndex(int $index)
    {
        $this->index = $index;
    }

    /**
     * Get the label of the renderable
     *
     * @return string
     * @api
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Set the label which shall be displayed next to the form element
     *
     * @param string $label
     * @return void
     * @api
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * Override this method in your custom Renderable if needed
     *
     * @param FormRuntime $formRuntime
     * @return void
     * @api
     */
    public function beforeRendering(FormRuntime $formRuntime)
    {
    }

    /**
     * This is a callback that is invoked by the Form Factory after the whole form has been built.
     * It can be used to add new form elements as children for complex form elements.
     *
     * Override this method in your custom Renderable if needed.
     *
     * @return void
     * @api
     */
    public function onBuildingFinished()
    {
    }
}
