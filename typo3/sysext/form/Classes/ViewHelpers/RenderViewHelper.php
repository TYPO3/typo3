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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Main Entry Point to render a Form into a Fluid Template
 *
 * Usage
 * =====
 *
 * <pre>
 * {namespace formvh=TYPO3\CMS\Form\ViewHelpers}
 * <formvh:render factoryClass="NameOfYourCustomFactoryClass" />
 * </pre>
 *
 * The factory class must implement {@link TYPO3\CMS\Form\Domain\Factory\FormFactoryInterface}.
 *
 * Scope: frontend
 * @api
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
     * @return void
     * @internal
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('persistenceIdentifier', 'string', 'The persistence identifier for the form.', false, null);
        $this->registerArgument('factoryClass', 'string', 'The fully qualified class name of the factory', false, ArrayFormFactory::class);
        $this->registerArgument('prototypeName', 'string', 'Name of the prototype to use', false, null);
        $this->registerArgument('overrideConfiguration', 'array', 'factory specific configuration', false, []);
    }

    /**
     * @param array $arguments
     * @param callable|\Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @public
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $persistenceIdentifier = $arguments['persistenceIdentifier'];
        $factoryClass = $arguments['factoryClass'];
        $prototypeName = $arguments['prototypeName'];
        $overrideConfiguration = $arguments['overrideConfiguration'];

        if (!empty($persistenceIdentifier)) {
            $formPersistenceManager = $renderingContext->getObjectManager()->get(FormPersistenceManagerInterface::class);
            $formConfiguration = $formPersistenceManager->load($persistenceIdentifier);
            ArrayUtility::mergeRecursiveWithOverrule(
                $formConfiguration,
                $overrideConfiguration
            );
            $overrideConfiguration = $formConfiguration;
            $overrideConfiguration['persistenceIdentifier'] = $persistenceIdentifier;
        }

        if (empty($prototypeName)) {
            $prototypeName = isset($overrideConfiguration['prototypeName']) ? $overrideConfiguration['prototypeName'] : 'standard';
        }

        $factory = $renderingContext->getObjectManager()->get($factoryClass);
        $formDefinition = $factory->build($overrideConfiguration, $prototypeName);
        $response = $renderingContext->getObjectManager()->get(Response::class, $renderingContext->getControllerContext()->getResponse());
        $form = $formDefinition->bind($renderingContext->getControllerContext()->getRequest(), $response);
        return $form->render();
    }
}
