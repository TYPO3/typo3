<?php
namespace TYPO3\CMS\Fluid\Core\Parser\SyntaxTree;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Node which will call a ViewHelper associated with this node.
 */
class ViewHelperNode extends \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
{
    /**
     * Class name of view helper
     *
     * @var string
     */
    protected $viewHelperClassName;

    /**
     * Arguments of view helper - References to RootNodes.
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The ViewHelper associated with this node
     *
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
     */
    protected $uninitializedViewHelper = null;

    /**
     * A mapping RenderingContext -> ViewHelper to only re-initialize ViewHelpers
     * when a context change occurs.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    protected $viewHelpersByContext = null;

    /**
     * Constructor.
     *
     * @param \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper $viewHelper The view helper
     * @param array $arguments Arguments of view helper - each value is a RootNode.
     */
    public function __construct(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper $viewHelper, array $arguments)
    {
        $this->uninitializedViewHelper = $viewHelper;
        $this->viewHelpersByContext = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class);
        $this->arguments = $arguments;
        $this->viewHelperClassName = get_class($this->uninitializedViewHelper);
    }

    /**
     * Returns the attached (but still uninitialized) ViewHelper for this ViewHelperNode.
     * We need this method because sometimes Interceptors need to ask some information from the ViewHelper.
     *
     * @return \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper the attached ViewHelper, if it is initialized
     */
    public function getUninitializedViewHelper()
    {
        return $this->uninitializedViewHelper;
    }

    /**
     * Get class name of view helper
     *
     * @return string Class Name of associated view helper
     */
    public function getViewHelperClassName()
    {
        return $this->viewHelperClassName;
    }

    /**
     * INTERNAL - only needed for compiling templates
     *
     * @return array
     * @internal
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Call the view helper associated with this object.
     *
     * First, it evaluates the arguments of the view helper.
     *
     * If the view helper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface,
     * it calls setChildNodes(array childNodes) on the view helper.
     *
     * Afterwards, checks that the view helper did not leave a variable lying around.
     *
     * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return string evaluated node after the view helper has been called.
     */
    public function evaluate(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
        if ($this->viewHelpersByContext->contains($renderingContext)) {
            $viewHelper = $this->viewHelpersByContext[$renderingContext];
            $viewHelper->resetState();
        } else {
            $viewHelper = clone $this->uninitializedViewHelper;
            $this->viewHelpersByContext->attach($renderingContext, $viewHelper);
        }

        $evaluatedArguments = [];
        if (count($viewHelper->prepareArguments())) {
            foreach ($viewHelper->prepareArguments() as $argumentName => $argumentDefinition) {
                if (isset($this->arguments[$argumentName])) {
                    $argumentValue = $this->arguments[$argumentName];
                    $evaluatedArguments[$argumentName] = $argumentValue->evaluate($renderingContext);
                } else {
                    $evaluatedArguments[$argumentName] = $argumentDefinition->getDefaultValue();
                }
            }
        }

        $viewHelper->setArguments($evaluatedArguments);
        $viewHelper->setViewHelperNode($this);
        $viewHelper->setRenderingContext($renderingContext);

        if ($viewHelper instanceof \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface) {
            $viewHelper->setChildNodes($this->childNodes);
        }

        $output = $viewHelper->initializeArgumentsAndRender();

        return $output;
    }

    /**
     * Clean up for serializing.
     *
     * @return array
     */
    public function __sleep()
    {
        return ['viewHelperClassName', 'arguments', 'childNodes'];
    }
}
