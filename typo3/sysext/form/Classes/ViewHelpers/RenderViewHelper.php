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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Factory\FormFactoryInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Main Entry Point to render a Form into a Fluid Template
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
        $persistenceIdentifier = $arguments['persistenceIdentifier'];
        $factoryClass = $arguments['factoryClass'];
        $prototypeName = $arguments['prototypeName'];
        $overrideConfiguration = $arguments['overrideConfiguration'];

        if (!empty($persistenceIdentifier)) {
            $formPersistenceManager = GeneralUtility::makeInstance(FormPersistenceManagerInterface::class);
            $formConfiguration = $formPersistenceManager->load($persistenceIdentifier);
            ArrayUtility::mergeRecursiveWithOverrule(
                $formConfiguration,
                $overrideConfiguration
            );
            $overrideConfiguration = $formConfiguration;
            $overrideConfiguration['persistenceIdentifier'] = $persistenceIdentifier;
        }

        if (empty($prototypeName)) {
            $prototypeName = $overrideConfiguration['prototypeName'] ?? 'standard';
        }

        // Even though getContainer() is internal, we can't get container injected here due to static scope
        /** @var FormFactoryInterface $factory */
        $factory = GeneralUtility::getContainer()->get($factoryClass);

        $formDefinition = $factory->build($overrideConfiguration, $prototypeName);
        /** @var RenderingContext $renderingContext */
        /** @var RequestInterface $request */
        $request = $renderingContext->getRequest();
        $form = $formDefinition->bind($request);

        return $form->render();
    }
}
