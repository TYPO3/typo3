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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
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

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        if (!empty($persistenceIdentifier)) {
            $formPersistenceManager = $objectManager->get(FormPersistenceManagerInterface::class);
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

        $factory = $objectManager->get($factoryClass);
        $formDefinition = $factory->build($overrideConfiguration, $prototypeName);
        $response = $renderingContext->getControllerContext()->getResponse() ?? $objectManager->get(Response::class);
        $form = $formDefinition->bind($renderingContext->getControllerContext()->getRequest(), $response);

        // If the controller context does not contain a response object, this viewhelper is used in a
        // fluid template rendered by the FluidTemplateContentObject. Handle the StopActionException
        // as there is no extbase dispatcher involved that catches that. */
        if ($renderingContext->getControllerContext()->getResponse() === null) {
            try {
                return $form->render();
            } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $exception) {
                return $response->shutdown();
            }
        }

        return $form->render();
    }
}
