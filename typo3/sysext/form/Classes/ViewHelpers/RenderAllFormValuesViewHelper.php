<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\ViewHelpers;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Renderer\RendererInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Renders the values of a form
 *
 * Scope: frontend
 * @api
 */
class RenderAllFormValuesViewHelper extends AbstractViewHelper
{

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize the arguments.
     *
     * @return void
     * @internal
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('renderable', RootRenderableInterface::class, 'A RootRenderableInterface instance', true);
        $this->registerArgument('as', 'string', 'The name within the template', false, 'formValue');
        $this->registerArgument('formRuntime', FormRuntime::class, 'A FormRuntime instance', false, null);
    }

    /**
     * @return string the rendered form values
     * @api
     */
    public function render()
    {
        $renderable = $this->arguments['renderable'];
        $as = $this->arguments['as'];
        $formRuntime = $this->arguments['formRuntime'];

        if ($renderable instanceof CompositeRenderableInterface) {
            $elements = $renderable->getRenderablesRecursively();
        } else {
            $elements = [$renderable];
        }

        if ($formRuntime === null) {
            /** @var RendererInterface $fluidFormRenderer */
            $fluidFormRenderer = $this->viewHelperVariableContainer->getView();
            $formRuntime = $fluidFormRenderer->getFormRuntime();
        }

        $output = '';
        foreach ($elements as $element) {
            if (!$element instanceof FormElementInterface || $element->getType() === 'Honeypot') {
                continue;
            }
            $value = $formRuntime[$element->getIdentifier()];

            $formValue = [
                'element' => $element,
                'value' => $value,
                'processedValue' => $this->processElementValue($element, $value, $formRuntime),
                'isMultiValue' => is_array($value) || $value instanceof \Iterator
            ];
            $this->templateVariableContainer->add($as, $formValue);
            $output .= $this->renderChildren();
            $this->templateVariableContainer->remove($as);
        }
        return $output;
    }

    /**
     * Converts the given value to a simple type (string or array) considering the underlying FormElement definition
     *
     * @param FormElementInterface $element
     * @param mixed $value
     * @param FormRuntime $formRuntime
     * @return mixed
     */
    protected function processElementValue(FormElementInterface $element, $value, FormRuntime $formRuntime)
    {
        $properties = $element->getProperties();
        if (isset($properties['options']) && is_array($properties['options'])) {
            $properties['options'] = TranslateElementPropertyViewHelper::renderStatic(
                ['element' => $element, 'property' => 'options', 'formRuntime' => $formRuntime],
                $this->buildRenderChildrenClosure(),
                $this->renderingContext
            );
            if (is_array($value)) {
                return $this->mapValuesToOptions($value, $properties['options']);
            } else {
                return $this->mapValueToOption($value, $properties['options']);
            }
        }
        if (is_object($value)) {
            return $this->processObject($element, $value);
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
     */
    protected function mapValuesToOptions(array $value, array $options): array
    {
        $result = [];
        foreach ($value as $key) {
            $result[] = $this->mapValueToOption($key, $options);
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
     */
    protected function mapValueToOption($value, array $options)
    {
        return isset($options[$value]) ? $options[$value] : $value;
    }

    /**
     * Converts the given $object to a string representation considering the $element FormElement definition
     *
     * @param FormElementInterface $element
     * @param object $object
     * @return string
     */
    protected function processObject(FormElementInterface $element, $object): string
    {
        $properties = $element->getProperties();
        if ($object instanceof \DateTime) {
            if (isset($properties['dateFormat'])) {
                $dateFormat = $properties['dateFormat'];
                if (isset($properties['displayTimeSelector']) && $properties['displayTimeSelector'] === true) {
                    $dateFormat .= ' H:i';
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
}
