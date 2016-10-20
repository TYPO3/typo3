<?php
namespace TYPO3\CMS\Fluid\Core\Compiler;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

class TemplateCompiler implements \TYPO3\CMS\Core\SingletonInterface
{
    const SHOULD_GENERATE_VIEWHELPER_INVOCATION = '##should_gen_viewhelper##';

    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend
     */
    protected $templateCache;

    /**
     * @var int
     */
    protected $variableCounter = 0;

    /**
     * @var array
     */
    protected $syntaxTreeInstanceCache = [];

    /**
     * @param \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $templateCache
     * @return void
     */
    public function setTemplateCache(\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $templateCache)
    {
        $this->templateCache = $templateCache;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function has($identifier)
    {
        $identifier = $this->sanitizeIdentifier($identifier);
        return $this->templateCache->has($identifier);
    }

    /**
     * @param string $identifier
     * @return \TYPO3\CMS\Fluid\Core\Parser\ParsedTemplateInterface
     */
    public function get($identifier)
    {
        $identifier = $this->sanitizeIdentifier($identifier);
        if (!isset($this->syntaxTreeInstanceCache[$identifier])) {
            $this->templateCache->requireOnce($identifier);
            $templateClassName = 'FluidCache_' . $identifier;
            $this->syntaxTreeInstanceCache[$identifier] = new $templateClassName();
        }
        return $this->syntaxTreeInstanceCache[$identifier];
    }

    /**
     * @param string $identifier
     * @param \TYPO3\CMS\Fluid\Core\Parser\ParsingState $parsingState
     * @return void
     */
    public function store($identifier, \TYPO3\CMS\Fluid\Core\Parser\ParsingState $parsingState)
    {
        $identifier = $this->sanitizeIdentifier($identifier);
        $this->variableCounter = 0;
        $generatedRenderFunctions = '';

        if ($parsingState->getVariableContainer()->exists('sections')) {
            $sections = $parsingState->getVariableContainer()->get('sections');
            // @todo refactor to $parsedTemplate->getSections()
            foreach ($sections as $sectionName => $sectionRootNode) {
                $generatedRenderFunctions .= $this->generateCodeForSection($this->convertListOfSubNodes($sectionRootNode), 'section_' . sha1($sectionName), 'section ' . $sectionName);
            }
        }
        $generatedRenderFunctions .= $this->generateCodeForSection($this->convertListOfSubNodes($parsingState->getRootNode()), 'render', 'Main Render function');
        $convertedLayoutNameNode = $parsingState->hasLayout() ? $this->convert($parsingState->getLayoutNameNode()) : ['initialization' => '', 'execution' => 'NULL'];

        $classDefinition = 'class FluidCache_' . $identifier . ' extends \\TYPO3\\CMS\\Fluid\\Core\\Compiler\\AbstractCompiledTemplate';

        $templateCode = <<<EOD
%s {

public function getVariableContainer() {
	// @todo
	return new \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer();
}
public function getLayoutName(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface \$renderingContext) {
\$currentVariableContainer = \$renderingContext->getTemplateVariableContainer();
\$self = \$this;
%s
return %s;
}
public function hasLayout() {
return %s;
}

%s

}
EOD;
        $templateCode = sprintf($templateCode,
                $classDefinition,
                $convertedLayoutNameNode['initialization'],
                $convertedLayoutNameNode['execution'],
                ($parsingState->hasLayout() ? 'TRUE' : 'FALSE'),
                $generatedRenderFunctions);
        $this->templateCache->set($identifier, $templateCode);
    }

    /**
     * Replaces special characters by underscores
     *
     * @see http://www.php.net/manual/en/language.variables.basics.php
     * @param string $identifier
     * @return string the sanitized identifier
     */
    protected function sanitizeIdentifier($identifier)
    {
        return preg_replace('([^a-zA-Z0-9_\\x7f-\\xff])', '_', $identifier);
    }

    /**
     * @param array $converted
     * @param string $expectedFunctionName
     * @param string $comment
     * @return string
     */
    protected function generateCodeForSection(array $converted, $expectedFunctionName, $comment)
    {
        $templateCode = <<<EOD
/**
 * %s
 */
public function %s(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface \$renderingContext) {
\$self = \$this;
\$currentVariableContainer = \$renderingContext->getTemplateVariableContainer();

%s

return %s;
}

EOD;
        return sprintf($templateCode, $comment, $expectedFunctionName, $converted['initialization'], $converted['execution']);
    }

    /**
     * Returns an array with two elements:
     * - initialization: contains PHP code which is inserted *before* the actual rendering call. Must be valid, i.e. end with semi-colon.
     * - execution: contains *a single PHP instruction* which needs to return the rendered output of the given element. Should NOT end with semi-colon.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node
     * @return array two-element array, see above
     * @throws \TYPO3\CMS\Fluid\Exception
     */
    protected function convert(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node)
    {
        if ($node instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode) {
            return $this->convertTextNode($node);
        } elseif ($node instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NumericNode) {
            return $this->convertNumericNode($node);
        } elseif ($node instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode) {
            return $this->convertViewHelperNode($node);
        } elseif ($node instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode) {
            return $this->convertObjectAccessorNode($node);
        } elseif ($node instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ArrayNode) {
            return $this->convertArrayNode($node);
        } elseif ($node instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode) {
            return $this->convertListOfSubNodes($node);
        } elseif ($node instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\BooleanNode) {
            return $this->convertBooleanNode($node);
        } else {
            throw new \TYPO3\CMS\Fluid\Exception('Syntax tree node type "' . get_class($node) . '" is not supported.');
        }
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode $node
     * @return array
     * @see convert()
     */
    protected function convertTextNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode $node)
    {
        return [
            'initialization' => '',
            'execution' => '\'' . $this->escapeTextForUseInSingleQuotes($node->getText()) . '\''
        ];
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NumericNode $node
     * @return array
     * @see convert()
     */
    protected function convertNumericNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NumericNode $node)
    {
        return [
            'initialization' => '',
            'execution' => $node->getValue()
        ];
    }

    /**
     * Convert a single ViewHelperNode into its cached representation. If the ViewHelper implements the "Compilable" facet,
     * the ViewHelper itself is asked for its cached PHP code representation. If not, a ViewHelper is built and then invoked.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode $node
     * @return array
     * @see convert()
     */
    protected function convertViewHelperNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode $node)
    {
        $initializationPhpCode = '// Rendering ViewHelper ' . $node->getViewHelperClassName() . LF;

        // Build up $arguments array
        $argumentsVariableName = $this->variableName('arguments');
        $initializationPhpCode .= sprintf('%s = array();', $argumentsVariableName) . LF;

        $alreadyBuiltArguments = [];
        foreach ($node->getArguments() as $argumentName => $argumentValue) {
            $converted = $this->convert($argumentValue);
            $initializationPhpCode .= $converted['initialization'];
            $initializationPhpCode .= sprintf('%s[\'%s\'] = %s;', $argumentsVariableName, $argumentName, $converted['execution']) . LF;
            $alreadyBuiltArguments[$argumentName] = true;
        }

        foreach ($node->getUninitializedViewHelper()->prepareArguments() as $argumentName => $argumentDefinition) {
            if (!isset($alreadyBuiltArguments[$argumentName])) {
                $initializationPhpCode .= sprintf('%s[\'%s\'] = %s;', $argumentsVariableName, $argumentName, var_export($argumentDefinition->getDefaultValue(), true)) . LF;
            }
        }

        // Build up closure which renders the child nodes
        $renderChildrenClosureVariableName = $this->variableName('renderChildrenClosure');
        $initializationPhpCode .= sprintf('%s = %s;', $renderChildrenClosureVariableName, $this->wrapChildNodesInClosure($node)) . LF;

        if ($node->getUninitializedViewHelper() instanceof \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface) {
            // ViewHelper is compilable
            $viewHelperInitializationPhpCode = '';
            $convertedViewHelperExecutionCode = $node->getUninitializedViewHelper()->compile($argumentsVariableName, $renderChildrenClosureVariableName, $viewHelperInitializationPhpCode, $node, $this);
            $initializationPhpCode .= $viewHelperInitializationPhpCode;
            if ($convertedViewHelperExecutionCode !== self::SHOULD_GENERATE_VIEWHELPER_INVOCATION) {
                return [
                    'initialization' => $initializationPhpCode,
                    'execution' => $convertedViewHelperExecutionCode
                ];
            }
        }

        // ViewHelper is not compilable, so we need to instanciate it directly and render it.
        $viewHelperVariableName = $this->variableName('viewHelper');

        $initializationPhpCode .= sprintf('%s = $self->getViewHelper(\'%s\', $renderingContext, \'%s\');', $viewHelperVariableName, $viewHelperVariableName, $node->getViewHelperClassName()) . LF;
        $initializationPhpCode .= sprintf('%s->setArguments(%s);', $viewHelperVariableName, $argumentsVariableName) . LF;
        $initializationPhpCode .= sprintf('%s->setRenderingContext($renderingContext);', $viewHelperVariableName) . LF;

        $initializationPhpCode .= sprintf('%s->setRenderChildrenClosure(%s);', $viewHelperVariableName, $renderChildrenClosureVariableName) . LF;

        $initializationPhpCode .= '// End of ViewHelper ' . $node->getViewHelperClassName() . LF;

        return [
            'initialization' => $initializationPhpCode,
            'execution' => sprintf('%s->initializeArgumentsAndRender()', $viewHelperVariableName)
        ];
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode $node
     * @return array
     * @see convert()
     */
    protected function convertObjectAccessorNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode $node)
    {
        $objectPathSegments = explode('.', $node->getObjectPath());
        $firstPathElement = array_shift($objectPathSegments);
        if ($objectPathSegments === []) {
            return [
                'initialization' => '',
                'execution' => sprintf('$currentVariableContainer->getOrNull(\'%s\')', $firstPathElement)
            ];
        } else {
            $executionCode = '\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($currentVariableContainer->getOrNull(\'%s\'), \'%s\', $renderingContext)';
            return [
                'initialization' => '',
                'execution' => sprintf($executionCode, $firstPathElement, implode('.', $objectPathSegments))
            ];
        }
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ArrayNode $node
     * @return array
     * @see convert()
     */
    protected function convertArrayNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ArrayNode $node)
    {
        $initializationPhpCode = '// Rendering Array' . LF;
        $arrayVariableName = $this->variableName('array');

        $initializationPhpCode .= sprintf('%s = array();', $arrayVariableName) . LF;

        foreach ($node->getInternalArray() as $key => $value) {
            if ($value instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode) {
                $converted = $this->convert($value);
                $initializationPhpCode .= $converted['initialization'];
                $initializationPhpCode .= sprintf('%s[\'%s\'] = %s;', $arrayVariableName, $key, $converted['execution']) . LF;
            } elseif (is_numeric($value)) {
                // this case might happen for simple values
                $initializationPhpCode .= sprintf('%s[\'%s\'] = %s;', $arrayVariableName, $key, $value) . LF;
            } else {
                // this case might happen for simple values
                $initializationPhpCode .= sprintf('%s[\'%s\'] = \'%s\';', $arrayVariableName, $key, $this->escapeTextForUseInSingleQuotes($value)) . LF;
            }
        }
        return [
            'initialization' => $initializationPhpCode,
            'execution' => $arrayVariableName
        ];
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node
     * @return array
     * @see convert()
     */
    public function convertListOfSubNodes(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node)
    {
        switch (count($node->getChildNodes())) {
            case 0:
                return [
                    'initialization' => '',
                    'execution' => 'NULL'
                ];
            case 1:
                $converted = $this->convert(current($node->getChildNodes()));

                return $converted;
            default:
                $outputVariableName = $this->variableName('output');
                $initializationPhpCode = sprintf('%s = \'\';', $outputVariableName) . LF;

                foreach ($node->getChildNodes() as $childNode) {
                    $converted = $this->convert($childNode);

                    $initializationPhpCode .= $converted['initialization'] . LF;
                    $initializationPhpCode .= sprintf('%s .= %s;', $outputVariableName, $converted['execution']) . LF;
                }

                return [
                    'initialization' => $initializationPhpCode,
                    'execution' => $outputVariableName
                ];
        }
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\BooleanNode $node
     * @return array
     * @see convert()
     */
    protected function convertBooleanNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\BooleanNode $node)
    {
        $initializationPhpCode = '// Rendering Boolean node' . LF;
        if ($node->getComparator() !== null) {
            $convertedLeftSide = $this->convert($node->getLeftSide());
            $convertedRightSide = $this->convert($node->getRightSide());

            return [
                'initialization' => $initializationPhpCode . $convertedLeftSide['initialization'] . $convertedRightSide['initialization'],
                'execution' => sprintf(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\BooleanNode::class . '::evaluateComparator(\'%s\', %s, %s)', $node->getComparator(), $convertedLeftSide['execution'], $convertedRightSide['execution'])
            ];
        } else {
            // simple case, no comparator.
            $converted = $this->convert($node->getSyntaxTreeNode());
            return [
                'initialization' => $initializationPhpCode . $converted['initialization'],
                'execution' => sprintf(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\BooleanNode::class . '::convertToBoolean(%s)', $converted['execution'])
            ];
        }
    }

    /**
     * @param string $text
     * @return string
     */
    protected function escapeTextForUseInSingleQuotes($text)
    {
        return str_replace(['\\', '\''], ['\\\\', '\\\''], $text);
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node
     * @return string
     */
    public function wrapChildNodesInClosure(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node)
    {
        $convertedSubNodes = $this->convertListOfSubNodes($node);
        if ($convertedSubNodes['execution'] === 'NULL') {
            return 'function() {return NULL;}';
        }

        $closure = '';
        $closure .= 'function() use ($renderingContext, $self) {' . LF;
        $closure .= '$currentVariableContainer = $renderingContext->getTemplateVariableContainer();' . LF;
        $closure .= $convertedSubNodes['initialization'];
        $closure .= sprintf('return %s;', $convertedSubNodes['execution']) . LF;
        $closure .= '}';
        return $closure;
    }

    /**
     * Returns a unique variable name by appending a global index to the given prefix
     *
     * @param string $prefix
     * @return string
     */
    public function variableName($prefix)
    {
        return '$' . $prefix . $this->variableCounter++;
    }
}
