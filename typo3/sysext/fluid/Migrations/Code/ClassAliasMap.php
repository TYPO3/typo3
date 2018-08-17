<?php
return [
    // Base classes removed in TYPO3 v9
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractViewHelper' => \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper::class,
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractConditionViewHelper' => \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper::class,
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractTagBasedViewHelper' => \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper::class,

    // Compiler/parser related aliases
    'TYPO3\\CMS\\Fluid\\Core\\Compiler\\TemplateCompiler' => \TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler::class,
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\InterceptorInterface' => \TYPO3Fluid\Fluid\Core\Parser\InterceptorInterface::class,
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\NodeInterface' => \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface::class,
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\AbstractNode' => \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class,
    'TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContextInterface' => \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface::class,
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\ViewHelperInterface' => \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface::class,
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Facets\\ChildNodeAccessInterface' => \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface::class,
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Facets\\CompilableInterface' => \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface::class,
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Facets\\PostParseInterface' => \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface::class,

    // Fluid-specific errors
    'TYPO3\\CMS\\Fluid\\Core\\Exception' => \TYPO3Fluid\Fluid\Core\Exception::class,
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Exception' => \TYPO3Fluid\Fluid\Core\ViewHelper\Exception::class,
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\Exception\\InvalidVariableException' => \TYPO3Fluid\Fluid\Core\Exception::class,
    'TYPO3\\CMS\\Fluid\\View\\Exception' => \TYPO3Fluid\Fluid\View\Exception::class,
    'TYPO3\\CMS\\Fluid\\View\\Exception\\InvalidSectionException' => \TYPO3Fluid\Fluid\View\Exception\InvalidSectionException::class,
    'TYPO3\\CMS\\Fluid\\View\\Exception\\InvalidTemplateResourceException' => \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException::class,

    // Fluid variable containers, ViewHelpers, interfaces
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\RootNode' => \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode::class,
    'TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode' => \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class,
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\ArgumentDefinition' => \TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition::class,
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TemplateVariableContainer' => \TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider::class,
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\ViewHelperVariableContainer' => \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer::class,
    'TYPO3\\CMS\\Fluid\\Core\\Variables\\CmsVariableProvider' => \TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider::class,

    // Semi API level classes; mainly used in unit tests
    'TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder' => \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder::class
];
