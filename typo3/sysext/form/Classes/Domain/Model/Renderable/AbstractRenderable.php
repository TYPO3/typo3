<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Model\Renderable;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It originated from the Neos.Form package (www.neos.io)
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
     * The position of this renderable inside the parent renderable.
     *
     * @var int
     */
    protected $index = 0;

    /**
     * The name of the template file of the renderable.
     *
     * @var string
     */
    protected $templateName = '';

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
     * Set the identifier of the element
     *
     * @param string $identifier
     * @api
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Set multiple properties of this object at once.
     * Every property which has a corresponding set* method can be set using
     * the passed $options array.
     *
     * @param array $options
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
            if (isset($options['properties']['placeholder'])) {
                GeneralUtility::deprecationLog('EXT:form - "properties.placeholder" is deprecated since TYPO3 v8 and will be removed in TYPO3 v9. Use "properties.fluidAdditionalAttributes.placeholder."');
                $options['properties']['fluidAdditionalAttributes']['placeholder'] = $options['properties']['placeholder'];
                unset($options['properties']['placeholder']);
            }

            foreach ($options['properties'] as $key => $value) {
                $this->setProperty($key, $value);
            }
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
            ['label', 'defaultValue', 'properties', 'renderingOptions', 'validators', 'formEditor']
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
        }
        throw new ValidatorPresetNotFoundException('The validator preset identified by "' . $validatorIdentifier . '" could not be found, or the implementationClassName was not specified.', 1328710202);
    }

    /**
     * Add a validator to the element
     *
     * @param ValidatorInterface $validator
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
     * @api
     */
    public function setDataType(string $dataType)
    {
        $formDefinition = $this->getRootForm();
        $formDefinition->getProcessingRule($this->getIdentifier())->setDataType($dataType);
    }

    /**
     * Get the classname of the renderer
     *
     * @return string
     * @api
     */
    public function getRendererClassName(): string
    {
        return $this->getRootForm()->getRendererClassName();
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
     * @internal
     */
    public function onRemoveFromParentRenderable()
    {
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRemoveFromParentRenderable'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRemoveFromParentRenderable'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRemoveFromParentRenderable'] as $className) {
                $hookObj = GeneralUtility::makeInstance($className);
                if (method_exists($hookObj, 'beforeRemoveFromParentRenderable')) {
                    $hookObj->beforeRemoveFromParentRenderable(
                        $this
                    );
                }
            }
        }

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
     * @api
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * Get the templateName name of the renderable
     *
     * @return string
     * @api
     */
    public function getTemplateName(): string
    {
        return empty($this->renderingOptions['templateName'])
            ? $this->type
            : $this->renderingOptions['templateName'];
    }

    /**
     * Override this method in your custom Renderable if needed
     *
     * @param FormRuntime $formRuntime
     * @api
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function beforeRendering(FormRuntime $formRuntime)
    {
        GeneralUtility::logDeprecatedFunction();
    }

    /**
     * This is a callback that is invoked by the Form Factory after the whole form has been built.
     * It can be used to add new form elements as children for complex form elements.
     *
     * Override this method in your custom Renderable if needed.
     *
     * @api
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function onBuildingFinished()
    {
    }
}
