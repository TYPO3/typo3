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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Renderer\RendererInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Translate form element properites.
 *
 * Scope: frontend / backend
 * @api
 */
class TranslateElementPropertyViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments.
     *
     * @return void
     * @internal
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('element', RootRenderableInterface::class, 'Form Element to translate', true);
        $this->registerArgument('property', 'string', 'Property to translate', false);
        $this->registerArgument('renderingOptionProperty', 'string', 'Property to translate', false);
        $this->registerArgument('formRuntime', FormRuntime::class, 'The form runtime', false);
    }

    /**
     * Return array element by key.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @api
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $element = $arguments['element'];
        $formRuntime = $arguments['formRuntime'];

        $property = null;
        if (!empty($arguments['property'])) {
            $property = $arguments['property'];
        } elseif (!empty($arguments['renderingOptionProperty'])) {
            $property = $arguments['renderingOptionProperty'];
        }

        if ($formRuntime === null) {
            /** @var RendererInterface $fluidFormRenderer */
            $fluidFormRenderer = $renderingContext->getViewHelperVariableContainer()->getView();
            $formRuntime = $fluidFormRenderer->getFormRuntime();
        }

        return TranslationService::getInstance()->translateFormElementValue($element, $property, $formRuntime);
    }
}
