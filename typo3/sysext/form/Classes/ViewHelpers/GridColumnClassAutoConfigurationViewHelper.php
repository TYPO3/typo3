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

use TYPO3\CMS\Form\Domain\Model\FormElements\GridContainerInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Scope: frontend
 */
class GridColumnClassAutoConfigurationViewHelper extends AbstractViewHelper
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
        $this->registerArgument('element', RootRenderableInterface::class, 'A RootRenderableInterface instance', true);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $formElement = $arguments['element'];

        $gridRowElement = $formElement->getParentRenderable();
        $gridContainerElement = $gridRowElement->getParentRenderable();
        $gridRowEChildElements = $gridRowElement->getElements();

        if ($gridContainerElement instanceof GridContainerInterface) {
            $gridViewPortConfiguration = $gridContainerElement->getProperties()['gridColumnClassAutoConfiguration'];
        } else {
            $gridViewPortConfiguration = $gridRowElement->getProperties()['gridColumnClassAutoConfiguration'];
        }

        if (empty($gridViewPortConfiguration)) {
            return '';
        }
        $gridSize = (int)$gridViewPortConfiguration['gridSize'];

        $columnsToCalculate = [];
        $usedColumns = [];
        foreach ($gridRowEChildElements as $childElement) {
            if (empty($childElement->getProperties()['gridColumnClassAutoConfiguration'])) {
                foreach ($gridViewPortConfiguration['viewPorts'] as $viewPortName => $configuration) {
                    $columnsToCalculate[$viewPortName]['elements']++;
                }
            } else {
                $gridColumnViewPortConfiguration = $childElement->getProperties()['gridColumnClassAutoConfiguration'];
                foreach ($gridViewPortConfiguration['viewPorts'] as $viewPortName => $configuration) {
                    $configuration = $gridColumnViewPortConfiguration['viewPorts'][$viewPortName];
                    if (
                        isset($configuration['numbersOfColumnsToUse'])
                        && (int)$configuration['numbersOfColumnsToUse'] > 0
                    ) {
                        $usedColumns[$viewPortName]['sum'] += (int)$configuration['numbersOfColumnsToUse'];
                        if ($childElement->getIdentifier() === $formElement->getIdentifier()) {
                            $usedColumns[$viewPortName]['concreteNumbersOfColumnsToUse'] = (int)$configuration['numbersOfColumnsToUse'];
                            if ($usedColumns[$viewPortName]['concreteNumbersOfColumnsToUse'] > $gridSize) {
                                $usedColumns[$viewPortName]['concreteNumbersOfColumnsToUse'] = $gridSize;
                            }
                        }
                    } else {
                        $columnsToCalculate[$viewPortName]['elements']++;
                    }
                }
            }
        }

        $classes = [];
        foreach ($gridViewPortConfiguration['viewPorts'] as $viewPortName => $configuration) {
            if (isset($usedColumns[$viewPortName]['concreteNumbersOfColumnsToUse'])) {
                $numbersOfColumnsToUse = $usedColumns[$viewPortName]['concreteNumbersOfColumnsToUse'];
            } else {
                $restColumnsToDivide = $gridSize - $usedColumns[$viewPortName]['sum'];
                $restElements = (int)$columnsToCalculate[$viewPortName]['elements'];

                if ($restColumnsToDivide < 1) {
                    $restColumnsToDivide = $gridSize;
                }
                if ($restElements < 1) {
                    $restElements = 1;
                }
                $numbersOfColumnsToUse = floor($restColumnsToDivide / $restElements);
            }

            $classes[] = str_replace(
                '{@numbersOfColumnsToUse}',
                $numbersOfColumnsToUse,
                $configuration['classPattern']
            );
        }

        return implode(' ', $classes);
    }
}
