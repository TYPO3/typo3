<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\ViewHelpers;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Renders the values of a form
 *
 * Scope: frontend
 */
class RenderAllFormValuesViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize the arguments.
     *
     * @internal
     */
    public function initializeArguments()
    {
        $this->registerArgument('renderable', RootRenderableInterface::class, 'A RootRenderableInterface instance', true);
        $this->registerArgument('as', 'string', 'The name within the template', false, 'formValue');
    }

    /**
     * Return array element by key.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string the rendered form values
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $renderable = $arguments['renderable'];
        $as = $arguments['as'];

        if (!$renderable->isEnabled()) {
            return '';
        }

        if ($renderable instanceof CompositeRenderableInterface) {
            $elements = $renderable->getRenderablesRecursively();
        } else {
            $elements = [$renderable];
        }

        $formRuntime = $renderingContext
            ->getViewHelperVariableContainer()
            ->get(RenderRenderableViewHelper::class, 'formRuntime');

        $output = '';
        foreach ($elements as $element) {
            $renderingOptions = $element->getRenderingOptions();

            if (
                !$element instanceof FormElementInterface
                || (isset($renderingOptions['_isCompositeFormElement']) && (bool)$renderingOptions['_isCompositeFormElement'] === true)
                || !$element->isEnabled()
                || self::hasDisabledParent($element)
            ) {
                continue;
            }

            if (
                (isset($renderingOptions['_isHiddenFormElement']) && (bool)$renderingOptions['_isHiddenFormElement'] === true)
                || (isset($renderingOptions['_isReadOnlyFormElement']) && (bool)$renderingOptions['_isReadOnlyFormElement'] === true)
            ) {
                trigger_error(
                    'Using the properties "renderingOptions._isHiddenFormElement" and "renderingOptions._isReadOnlyFormElement" will be removed in TYPO3 v10.0. Use variants instead.',
                    E_USER_DEPRECATED
                );
                continue;
            }

            $value = $formRuntime[$element->getIdentifier()];

            $formValue = [
                'element' => $element,
                'value' => $value,
                'processedValue' => self::processElementValue($element, $value, $renderChildrenClosure, $renderingContext),
                'isMultiValue' => is_array($value) || $value instanceof \Iterator
            ];
            $renderingContext->getVariableProvider()->add($as, $formValue);
            $output .= $renderChildrenClosure();
            $renderingContext->getVariableProvider()->remove($as);
        }
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
        if (isset($properties['options']) && is_array($properties['options'])) {
            $properties['options'] = TranslateElementPropertyViewHelper::renderStatic(
                ['element' => $element, 'property' => 'options'],
                $renderChildrenClosure,
                $renderingContext
            );
            if (is_array($value)) {
                return self::mapValuesToOptions($value, $properties['options']);
            }
            return self::mapValueToOption($value, $properties['options']);
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
        $properties = $element->getProperties();
        if ($object instanceof \DateTime) {
            if (
                $element->getType() === 'DatePicker'
                && isset($properties['dateFormat'])
            ) {
                $dateFormat = $properties['dateFormat'];
                if (isset($properties['displayTimeSelector']) && $properties['displayTimeSelector'] === true) {
                    $dateFormat .= ' H:i';
                }
            } elseif ($element->getType() === 'Date') {
                if (isset($properties['displayFormat'])) {
                    $dateFormat = $properties['displayFormat'];
                } else {
                    $dateFormat = 'Y-m-d';
                }
            } else {
                $dateFormat = \DateTime::W3C;
            }

            return $object->format($dateFormat);
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
    public static function hasDisabledParent(RenderableInterface $renderable): bool
    {
        while ($renderable = $renderable->getParentRenderable()) {
            if ($renderable instanceof RenderableInterface && !$renderable->isEnabled()) {
                return true;
            }
        }

        return false;
    }
}
