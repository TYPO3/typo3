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

namespace TYPO3\CMS\Form\ViewHelpers;

use TYPO3\CMS\Core\Country\CountryProvider;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\StringableFormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3Fluid\Fluid\Core\Variables\ScopedVariableProvider;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders a single value of a form
 *
 * Scope: frontend
 */
final class RenderFormValueViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('renderable', RenderableInterface::class, 'A renderable element', true);
        $this->registerArgument('as', 'string', 'The name within the template', false, 'formValue');
    }

    /**
     * Return array element by key
     */
    public function render(): string
    {
        $element = $this->arguments['renderable'];
        if (!$element instanceof FormElementInterface || !self::isEnabled($element)) {
            return '';
        }
        $renderingOptions = $element->getRenderingOptions();
        if ($renderingOptions['_isSection'] ?? false) {
            $data = [
                'element' => $element,
                'isSection' => true,
            ];
        } elseif ($renderingOptions['_isCompositeFormElement'] ?? false) {
            return '';
        } else {
            $formRuntime = $this->renderingContext
                ->getViewHelperVariableContainer()
                ->get(RenderRenderableViewHelper::class, 'formRuntime');
            $value = $formRuntime[$element->getIdentifier()];
            $data = [
                'element' => $element,
                'value' => $value,
                'processedValue' => $this->processElementValue($element, $value),
                'isMultiValue' => is_iterable($value),
            ];
        }
        $variableProvider = new ScopedVariableProvider($this->renderingContext->getVariableProvider(), new StandardVariableProvider([$this->arguments['as'] => $data]));
        $this->renderingContext->setVariableProvider($variableProvider);
        $output = (string)$this->renderChildren();
        $this->renderingContext->setVariableProvider($variableProvider->getGlobalVariableProvider());
        return $output;
    }

    /**
     * Converts the given value to a simple type (string or array) considering the underlying FormElement definition.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function processElementValue(
        FormElementInterface $element,
        $value
    ) {
        $properties = $element->getProperties();
        $options = $properties['options'] ?? null;
        if ($element->getType() === 'CountrySelect') {
            $country = GeneralUtility::makeInstance(CountryProvider::class)->getByIsoCode($value);
            if ($country !== null) {
                return $country->getName();
            }
        }
        if (is_array($options)) {
            $options = (array)$this->renderingContext->getViewHelperInvoker()->invoke(
                TranslateElementPropertyViewHelper::class,
                ['element' => $element, 'property' => 'options'],
                $this->renderingContext,
                $this->renderChildren(...),
            );
            if (is_array($value)) {
                return self::mapValuesToOptions($value, $options);
            }
            return self::mapValueToOption($value, $options);
        }
        if (is_object($value)) {
            return self::processObject($element, $value);
        }
        return $value;
    }

    /**
     * Replaces the given values (=keys) with the corresponding elements in $options.
     *
     * @see mapValueToOption()
     */
    protected static function mapValuesToOptions(array $value, array $options): array
    {
        $result = [];
        foreach ($value as $key) {
            $result[] = self::mapValueToOption($key, $options);
        }
        return $result;
    }

    /**
     * Replaces the given value (=key) with the corresponding element in $options
     * If the key does not exist in $options, it is returned without modification
     *
     * @param mixed $value
     * @return mixed
     */
    protected static function mapValueToOption($value, array $options)
    {
        return $options[$value] ?? $value;
    }

    /**
     * Converts the given $object to a string representation considering the $element FormElement definition.
     *
     * @param object $object
     */
    protected static function processObject(FormElementInterface $element, $object): string
    {
        if ($element instanceof StringableFormElementInterface) {
            return $element->valueToString($object);
        }

        if ($object instanceof \DateTime) {
            return $object->format(\DateTimeInterface::W3C);
        }

        if ($object instanceof File || $object instanceof FileReference) {
            if ($object instanceof FileReference) {
                $object = $object->getOriginalResource();
            }

            return $object->getName();
        }

        if (method_exists($object, '__toString')) {
            return (string)$object;
        }

        return 'Object [' . get_class($object) . ']';
    }

    protected static function isEnabled(RenderableInterface $renderable): bool
    {
        if (!$renderable->isEnabled()) {
            return false;
        }
        while ($renderable = $renderable->getParentRenderable()) {
            if ($renderable instanceof RenderableInterface && !$renderable->isEnabled()) {
                return false;
            }
        }
        return true;
    }
}
