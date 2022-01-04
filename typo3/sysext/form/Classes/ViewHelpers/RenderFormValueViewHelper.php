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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\StringableFormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Renders a single value of a form
 *
 * Scope: frontend
 */
class RenderFormValueViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize the arguments
     *
     * @internal
     */
    public function initializeArguments()
    {
        $this->registerArgument('renderable', RenderableInterface::class, 'A renderable element', true);
        $this->registerArgument('as', 'string', 'The name within the template', false, 'formValue');
    }

    /**
     * Return array element by key
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string the rendered form values
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $element = $arguments['renderable'];

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
            $formRuntime = $renderingContext
                ->getViewHelperVariableContainer()
                ->get(RenderRenderableViewHelper::class, 'formRuntime');
            $value = $formRuntime[$element->getIdentifier()];
            $data = [
                'element' => $element,
                'value' => $value,
                'processedValue' => self::processElementValue($element, $value, $renderChildrenClosure, $renderingContext),
                'isMultiValue' => is_iterable($value),
            ];
        }

        $as = $arguments['as'];
        $renderingContext->getVariableProvider()->add($as, $data);
        $output = $renderChildrenClosure();
        $renderingContext->getVariableProvider()->remove($as);

        return $output;
    }

    /**
     * Converts the given value to a simple type (string or array) considering the underlying FormElement definition
     *
     * @param FormElementInterface $element
     * @param mixed $value
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     * @internal
     */
    public static function processElementValue(
        FormElementInterface $element,
        $value,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $properties = $element->getProperties();
        $options = $properties['options'] ?? null;
        if (is_array($options)) {
            $options = (array)TranslateElementPropertyViewHelper::renderStatic(
                ['element' => $element, 'property' => 'options'],
                $renderChildrenClosure,
                $renderingContext
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
     * Replaces the given values (=keys) with the corresponding elements in $options
     * @see mapValueToOption()
     *
     * @param array $value
     * @param array $options
     * @return array
     * @internal
     */
    public static function mapValuesToOptions(array $value, array $options): array
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
     * @param array $options
     * @return mixed
     * @internal
     */
    public static function mapValueToOption($value, array $options)
    {
        return $options[$value] ?? $value;
    }

    /**
     * Converts the given $object to a string representation considering the $element FormElement definition
     *
     * @param FormElementInterface $element
     * @param object $object
     * @return string
     * @internal
     */
    public static function processObject(FormElementInterface $element, $object): string
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

    /**
     * @param RenderableInterface $renderable
     * @return bool
     * @internal
     */
    public static function isEnabled(RenderableInterface $renderable): bool
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
