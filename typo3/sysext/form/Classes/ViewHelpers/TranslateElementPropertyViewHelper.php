<?php
declare(strict_types = 1);
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

use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Translate form element properties.
 *
 * Scope: frontend / backend
 */
class TranslateElementPropertyViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments.
     *
     * @internal
     */
    public function initializeArguments()
    {
        $this->registerArgument('element', RootRenderableInterface::class, 'Form Element to translate', true);
        $this->registerArgument('property', 'mixed', 'Property to translate', false);
        $this->registerArgument('renderingOptionProperty', 'mixed', 'Property to translate', false);
    }

    /**
     * Return array element by key.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string|array
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        static::assertArgumentTypes($arguments);

        $element = $arguments['element'];

        $property = null;
        if (!empty($arguments['property'])) {
            $property = $arguments['property'];
        } elseif (!empty($arguments['renderingOptionProperty'])) {
            $property = $arguments['renderingOptionProperty'];
        }

        if (empty($property)) {
            $propertyParts = [];
        } elseif (is_array($property)) {
            $propertyParts = $property;
        } else {
            $propertyParts = [$property];
        }

        /** @var FormRuntime $formRuntime */
        $formRuntime = $renderingContext
            ->getViewHelperVariableContainer()
            ->get(RenderRenderableViewHelper::class, 'formRuntime');

        return TranslationService::getInstance()->translateFormElementValue($element, $propertyParts, $formRuntime);
    }

    /**
     * @param array $arguments
     */
    protected static function assertArgumentTypes(array $arguments)
    {
        foreach (['property', 'renderingOptionProperty'] as $argumentName) {
            if (
                !isset($arguments[$argumentName])
                || is_string($arguments[$argumentName])
                || is_array($arguments[$argumentName])
            ) {
                continue;
            }
            throw new Exception(
                sprintf(
                    'Arguments "%s" either must be string or array',
                    $argumentName
                ),
                1504871830
            );
        }
    }
}
