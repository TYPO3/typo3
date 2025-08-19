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

namespace TYPO3\CMS\Form\ViewHelpers;

use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Translate form element properties.
 *
 * Scope: frontend / backend
 */
final class TranslateElementPropertyViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly TranslationService $translationService
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('element', RootRenderableInterface::class, 'Form Element to translate', true);
        $this->registerArgument('property', 'mixed', 'Property to translate');
        $this->registerArgument('renderingOptionProperty', 'mixed', 'Property to translate');
    }

    /**
     * Return array element by key.
     */
    public function render(): array|string
    {
        self::assertArgumentTypes($this->arguments);
        $element = $this->arguments['element'];
        $property = null;
        if (!empty($this->arguments['property'])) {
            $property = $this->arguments['property'];
        } elseif (!empty($this->arguments['renderingOptionProperty'])) {
            $property = $this->arguments['renderingOptionProperty'];
        }
        if (empty($property)) {
            $propertyParts = [];
        } elseif (is_array($property)) {
            $propertyParts = $property;
        } else {
            $propertyParts = [$property];
        }
        /** @var FormRuntime $formRuntime */
        $formRuntime = $this->renderingContext
            ->getViewHelperVariableContainer()
            ->get(RenderRenderableViewHelper::class, 'formRuntime');
        return $this->translationService->translateFormElementValue($element, $propertyParts, $formRuntime);
    }

    private static function assertArgumentTypes(array $arguments): void
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
