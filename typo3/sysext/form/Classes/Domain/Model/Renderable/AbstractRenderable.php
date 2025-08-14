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

namespace TYPO3\CMS\Form\Domain\Model\Renderable;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\CMS\Form\Domain\Model\Exception\FormDefinitionConsistencyException;
use TYPO3\CMS\Form\Domain\Model\Exception\ValidatorPresetNotFoundException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

/**
 * Convenience base class which implements common functionality for most
 * classes which implement RenderableInterface.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 * @internal
 */
abstract class AbstractRenderable implements RenderableInterface, VariableRenderableInterface
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
     * @var CompositeRenderableInterface|null
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
     * associative array of rendering variants
     *
     * @var array
     */
    protected $variants = [];

    protected ?ValidatorResolver $validatorResolver = null;

    protected ?ServerRequestInterface $request = null;

    /**
     * Get the type of the renderable
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the identifier of the element
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Set the identifier of the element
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function setRequest(?ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Set multiple properties of this object at once.
     * Every property which has a corresponding set* method can be set using
     * the passed $options array.
     */
    public function setOptions(array $options, bool $resetValidators = false)
    {
        if (isset($options['label'])) {
            $this->setLabel($options['label']);
        }

        if (isset($options['renderingOptions'])) {
            foreach ($options['renderingOptions'] as $key => $value) {
                $this->setRenderingOption($key, $value);
            }
        }

        if (isset($options['validators'])) {
            $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
            $configurationHashes = $runtimeCache->get('formAbstractRenderableConfigurationHashes') ?: [];

            if ($resetValidators) {
                $this->getRootForm()->getProcessingRule($this->getIdentifier())->removeAllValidators();
                $configurationHashes = [];
            }

            foreach ($options['validators'] as $validatorConfiguration) {
                $configurationHash = md5(
                    spl_object_hash($this) .
                    json_encode($validatorConfiguration)
                );
                if (in_array($configurationHash, $configurationHashes)) {
                    continue;
                }
                $this->createValidator($validatorConfiguration['identifier'], $validatorConfiguration['options'] ?? []);
                $configurationHashes[] = $configurationHash;
                $runtimeCache->set('formAbstractRenderableConfigurationHashes', $configurationHashes);
            }
        }

        if (isset($options['variants'])) {
            foreach ($options['variants'] as $variantConfiguration) {
                $this->createVariant($variantConfiguration);
            }
        }

        ArrayUtility::assertAllArrayKeysAreValid(
            $options,
            ['label', 'defaultValue', 'properties', 'renderingOptions', 'validators', 'formEditor', 'variants']
        );
    }

    /**
     * Create a validator for the element.
     *
     * @throws ValidatorPresetNotFoundException
     */
    public function createValidator(string $validatorIdentifier, array $options = []): ?ValidatorInterface
    {
        $validatorsDefinition = $this->getRootForm()->getValidatorsDefinition();
        if (isset($validatorsDefinition[$validatorIdentifier]) && is_array($validatorsDefinition[$validatorIdentifier]) && isset($validatorsDefinition[$validatorIdentifier]['implementationClassName'])) {
            $implementationClassName = $validatorsDefinition[$validatorIdentifier]['implementationClassName'];
            $defaultOptions = $validatorsDefinition[$validatorIdentifier]['options'] ?? [];
            ArrayUtility::mergeRecursiveWithOverrule($defaultOptions, $options);
            // @todo: It would be great if Renderable's and FormElements could use DI, but especially
            //        FormElements which extend AbstractRenderable pollute __construct() with manual
            //        arguments. To retrieve the ValidatorResolver, we have to fall back to getContainer()
            //        for now, until this has been resolved.
            if ($this->validatorResolver === null) {
                $container = GeneralUtility::getContainer();
                $this->validatorResolver = $container->get(ValidatorResolver::class);
            }
            $validator = $this->validatorResolver->createValidator($implementationClassName, $defaultOptions, $this->request);
            if ($validator !== null) {
                $this->addValidator($validator);
            }
            return $validator;
        }
        throw new ValidatorPresetNotFoundException('The validator preset identified by "' . $validatorIdentifier . '" could not be found, or the implementationClassName was not specified.', 1328710202);
    }

    /**
     * Add a validator to the element.
     */
    public function addValidator(ValidatorInterface $validator)
    {
        $formDefinition = $this->getRootForm();
        $formDefinition->getProcessingRule($this->getIdentifier())->addValidator($validator);
    }

    /**
     * Get all validators on the element
     *
     * @internal
     */
    public function getValidators(): \SplObjectStorage
    {
        $formDefinition = $this->getRootForm();
        return $formDefinition->getProcessingRule($this->getIdentifier())->getValidators();
    }

    /**
     * Set the datatype
     */
    public function setDataType(string $dataType)
    {
        $formDefinition = $this->getRootForm();
        $formDefinition->getProcessingRule($this->getIdentifier())->setDataType($dataType);
    }

    /**
     * Get the classname of the renderer
     */
    public function getRendererClassName(): string
    {
        return $this->getRootForm()->getRendererClassName();
    }

    /**
     * Get all rendering options
     */
    public function getRenderingOptions(): array
    {
        return $this->renderingOptions;
    }

    /**
     * Set the rendering option $key to $value.
     *
     * @param mixed $value
     * @return mixed
     */
    public function setRenderingOption(string $key, $value)
    {
        if (is_array($value) && isset($this->renderingOptions[$key]) && is_array($this->renderingOptions[$key])) {
            ArrayUtility::mergeRecursiveWithOverrule($this->renderingOptions[$key], $value);
            $this->renderingOptions[$key] = ArrayUtility::removeNullValuesRecursive($this->renderingOptions[$key]);
        } elseif ($value === null) {
            unset($this->renderingOptions[$key]);
        } else {
            $this->renderingOptions[$key] = $value;
        }
    }

    /**
     * Get the parent renderable
     *
     * @return CompositeRenderableInterface|null
     */
    public function getParentRenderable()
    {
        return $this->parentRenderable;
    }

    /**
     * Set the parent renderable
     */
    public function setParentRenderable(CompositeRenderableInterface $parentRenderable)
    {
        $this->parentRenderable = $parentRenderable;
        $this->registerInFormIfPossible();
    }

    /**
     * Get the root form this element belongs to
     *
     * @throws FormDefinitionConsistencyException
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
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRemoveFromParentRenderable'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'beforeRemoveFromParentRenderable')) {
                $hookObj->beforeRemoveFromParentRenderable(
                    $this
                );
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
     * @internal
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * Set the index of the renderable
     *
     * @internal
     */
    public function setIndex(int $index)
    {
        $this->index = $index;
    }

    /**
     * Get the label of the renderable
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Set the label which shall be displayed next to the form element
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * Get the templateName name of the renderable
     */
    public function getTemplateName(): string
    {
        return empty($this->renderingOptions['templateName'])
            ? $this->type
            : $this->renderingOptions['templateName'];
    }

    /**
     * Returns whether this renderable is enabled
     */
    public function isEnabled(): bool
    {
        return !isset($this->renderingOptions['enabled']) || (bool)$this->renderingOptions['enabled'] === true;
    }

    /**
     * Get all rendering variants
     *
     * @return RenderableVariantInterface[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    public function createVariant(array $options): RenderableVariantInterface
    {
        $identifier = $options['identifier'] ?? '';
        unset($options['identifier']);

        $variant = GeneralUtility::makeInstance(RenderableVariant::class, $identifier, $options, $this);

        $this->addVariant($variant);
        return $variant;
    }

    /**
     * Adds the specified variant to this form element
     */
    public function addVariant(RenderableVariantInterface $variant)
    {
        $this->variants[$variant->getIdentifier()] = $variant;
    }

    /**
     * Apply the specified variant to this form element
     * regardless of their conditions
     */
    public function applyVariant(RenderableVariantInterface $variant)
    {
        $variant->apply();
    }
}
