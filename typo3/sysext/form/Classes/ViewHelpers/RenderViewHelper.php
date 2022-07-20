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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Core\FormRequestHandler;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Entry Point to render a Form into a Fluid Template.
 * Please read the inline docs from TYPO3\CMS\Form\Core\FormRequestHandler for
 * more details about the rendering / bootstrap process.
 *
 * Usage
 * =====
 *
 * Default::
 *
 *    {namespace formvh=TYPO3\CMS\Form\ViewHelpers}
 *    <formvh:render factoryClass="NameOfYourCustomFactoryClass" />
 *
 * The factory class must implement :php:`TYPO3\CMS\Form\Domain\Factory\FormFactoryInterface`.
 *
 * Scope: frontend
 */
final class RenderViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('persistenceIdentifier', 'string', 'The persistence identifier for the form.', false);
        $this->registerArgument('factoryClass', 'string', 'The fully qualified class name of the factory', false, ArrayFormFactory::class);
        $this->registerArgument('prototypeName', 'string', 'Name of the prototype to use', false);
        $this->registerArgument('overrideConfiguration', 'array', 'factory specific configuration', false, []);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): ?string
    {
        $request = $renderingContext->getRequest();
        $extensionName = $request instanceof Request ? $request->getControllerExtensionName() : 'Form';
        $contentObjectRenderer = self::getContentObjectRenderer();

        return $contentObjectRenderer->cObjGetSingle('USER', [
            'userFunc' => FormRequestHandler::class . '->process',
            'persistenceIdentifier' => $arguments['persistenceIdentifier'],
            'factoryClass' => $arguments['factoryClass'],
            'prototypeName' => $arguments['prototypeName'],
            'configuration' => $arguments['overrideConfiguration'],
            'extensionName' => $extensionName,
            'pluginName' => $request->getPluginName(),
        ]);
    }

    private static function getContentObjectRenderer(): ContentObjectRenderer
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $contentObjectRenderer = $configurationManager->getContentObject();

        if ($contentObjectRenderer === null) {
            $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        }

        return $contentObjectRenderer;
    }
}
