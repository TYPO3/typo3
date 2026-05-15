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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Event\ModifyFormValueForRenderingEvent;
use TYPO3\CMS\Form\Service\FormValueResolver;
use TYPO3Fluid\Fluid\Core\Variables\ScopedVariableProvider;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders a single value of a form
 *
 * Scope: frontend
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-form-renderformvalue
 */
final class RenderFormValueViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly FormValueResolver $formValueResolver,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

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
                'processedValue' => $this->formValueResolver->resolveDisplayValue($element, $value, $formRuntime),
                'isMultiValue' => is_iterable($value),
            ];
        }
        $event = new ModifyFormValueForRenderingEvent($data);
        $this->eventDispatcher->dispatch($event);

        $variableProvider = new ScopedVariableProvider($this->renderingContext->getVariableProvider(), new StandardVariableProvider([$this->arguments['as'] => $event->getData()]));
        $this->renderingContext->setVariableProvider($variableProvider);
        $output = (string)$this->renderChildren();
        $this->renderingContext->setVariableProvider($variableProvider->getGlobalVariableProvider());
        return $output;
    }

    private static function isEnabled(RenderableInterface $renderable): bool
    {
        if (!$renderable->isEnabled()) {
            return false;
        }
        while ($renderable = $renderable->getParentRenderable()) {
            if (!$renderable->isEnabled()) {
                return false;
            }
        }
        return true;
    }
}
