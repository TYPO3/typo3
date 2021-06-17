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
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
class RenderViewHelper extends AbstractViewHelper
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
        $this->registerArgument('persistenceIdentifier', 'string', 'The persistence identifier for the form.', false, null);
        $this->registerArgument('factoryClass', 'string', 'The fully qualified class name of the factory', false, ArrayFormFactory::class);
        $this->registerArgument('prototypeName', 'string', 'Name of the prototype to use', false, null);
        $this->registerArgument('overrideConfiguration', 'array', 'factory specific configuration', false, []);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
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
        $container = GeneralUtility::getContainer();
        if ($container->has($factoryClass)) {
            /** @var FormFactoryInterface $factory */
            $factory = $container->get($factoryClass);
        } else {
            // @deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
            /** @var FormFactoryInterface $factory */
            $factory = GeneralUtility::makeInstance(ObjectManager::class)->get($factoryClass);
        }

        $formDefinition = $factory->build($overrideConfiguration, $prototypeName);
        $form = $formDefinition->bind($renderingContext->getRequest());

        // If the controller context does not contain a response object, this viewhelper is used in a
        // fluid template rendered by the FluidTemplateContentObject. Handle the StopActionException
        // as there is no extbase dispatcher involved that catches that. */
        try {
            return $form->render();
        } catch (StopActionException $exception) {
            // @deprecated since v11, will be removed in v12: StopActionException is deprecated, drop this catch block.
            // RedirectFinisher for throws a PropagateResponseException instead which bubbles up into Middleware.
            trigger_error(
                'Throwing StopActionException is deprecated. If really needed, throw a (internal) PropagateResponseException'
                . ' instead, for now. Note this is subject to change.',
                E_USER_DEPRECATED
            );
            return $exception->getResponse()->getBody()->getContents();
        }
    }
}
