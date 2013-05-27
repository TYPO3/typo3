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

class TemplateCompiler implements \TYPO3\CMS\Core\SingletonInterface {

	const SHOULD_GENERATE_VIEWHELPER_INVOCATION = '##should_gen_viewhelper##';

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend
	 */
	protected $templateCache;

	/**
	 * @var integer
	 */
	protected $variableCounter = 0;

	/**
	 * @var array
	 */
	protected $syntaxTreeInstanceCache = array();

	/**
	 * @param \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $templateCache
	 * @return void
	 */
	public function setTemplateCache(\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $templateCache) {
		$this->templateCache = $templateCache;
	}

	/**
	 * @param string $identifier
	 * @return boolean
	 */
	public function has($identifier) {
		$identifier = $this->sanitizeIdentifier($identifier);
		return $this->templateCache->has($identifier);
	}

	/**
	 * @param string $identifier
	 * @return \TYPO3\CMS\Fluid\Core\Parser\ParsedTemplateInterface
	 */
	public function get($identifier) {
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
	public function store($identifier, \TYPO3\CMS\Fluid\Core\Parser\ParsingState $parsingState) {
		$identifier = $this->sanitizeIdentifier($identifier);
		$this->variableCounter = 0;
		$generatedRenderFunctions = '';

		if ($parsingState->getVariableContainer()->exists('sections')) {
			$sections = $parsingState->getVariableContainer()->get('sections');
			// TODO: refactor to $parsedTemplate->getSections()
			foreach ($sections as $sectionName => $sectionRootNode) {
				$generatedRenderFunctions .= $this->generateCodeForSection($this->convertListOfSubNodes($sectionRootNode), 'section_' . sha1($sectionName), 'section ' . $sectionName);
			}
		}
		$generatedRenderFunctions .= $this->generateCodeForSection($this->convertListOfSubNodes($parsingState->getRootNode()), 'render', 'Main Render function');
		$convertedLayoutNameNode = $parsingState->hasLayout() ? $this->convert($parsingState->getLayoutNameNode()) : array('initialization' => '', 'execution' => 'NULL');

		$classDefinition = 'class FluidCache_' . $identifier . ' extends \\TYPO3\\CMS\\Fluid\\Core\\Compiler\\AbstractCompiledTemplate';

		$templateCode = <<<EOD
%s {

public function getVariableContainer() {
	// TODO
	return new \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer();
}
public function getLayoutName(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface \$renderingContext) {
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
	protected function sanitizeIdentifier($identifier) {
		return preg_replace('([^a-zA-Z0-9_\\x7f-\\xff])', '_', $identifier);
	}

	/**
	 * @param array $converted
	 * @param string $expectedFunctionName
	 * @param string $comment
	 * @return string
	 */
	protected function generateCodeForSection(array $converted, $expectedFunctionName, $comment) {
		$templateCode = <<<EOD
/**
 * %s
 */
public function %s(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface \$renderingContext) {
\$self = \$this;
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
	protected function convert(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node) {
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
	protected function convertTextNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode $node) {
		return array(
			'initialization' => '',
			'execution' => '\'' . $this->escapeTextForUseInSingleQuotes($node->getText()) . '\''
		);
	}

	/**
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NumericNode $node
	 * @return array
	 * @see convert()
	 */
	protected function convertNumericNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NumericNode $node) {
		return array(
			'initialization' => '',
			'execution' => $node->getValue()
		);
	}

	/**
	 * Convert a single ViewHelperNode into its cached representation. If the ViewHelper implements the "Compilable" facet,
	 * the ViewHelper itself is asked for its cached PHP code representation. If not, a ViewHelper is built and then invoked.
	 *
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode $node
	 * @return array
	 * @see convert()
	 */
	protected function convertViewHelperNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode $node) {
		$initializationPhpCode = '// Rendering ViewHelper ' . $node->getViewHelperClassName() . chr(10);

		// Build up $arguments array
		$argumentsVariableName = $this->variableName('arguments');
		$initializationPhpCode .= sprintf('%s = array();', $argumentsVariableName) . chr(10);

		$alreadyBuiltArguments = array();
		foreach ($node->getArguments() as $argumentName => $argumentValue) {
			$converted = $this->convert($argumentValue);
			$initializationPhpCode .= $converted['initialization'];
			$initializationPhpCode .= sprintf('%s[\'%s\'] = %s;', $argumentsVariableName, $argumentName, $converted['execution']) . chr(10);
			$alreadyBuiltArguments[$argumentName] = TRUE;
		}

		foreach ($node->getUninitializedViewHelper()->prepareArguments() as $argumentName => $argumentDefinition) {
			if (!isset($alreadyBuiltArguments[$argumentName])) {
				$initializationPhpCode .= sprintf('%s[\'%s\'] = %s;', $argumentsVariableName, $argumentName, var_export($argumentDefinition->getDefaultValue(), TRUE)) . chr(10);
			}
		}

		// Build up closure which renders the child nodes
		$renderChildrenClosureVariableName = $this->variableName('renderChildrenClosure');
		$initializationPhpCode .= sprintf('%s = %s;', $renderChildrenClosureVariableName, $this->wrapChildNodesInClosure($node)) . chr(10);

		if ($node->getUninitializedViewHelper() instanceof \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface) {
			// ViewHelper is compilable
			$viewHelperInitializationPhpCode = '';
			$convertedViewHelperExecutionCode = $node->getUninitializedViewHelper()->compile($argumentsVariableName, $renderChildrenClosureVariableName, $viewHelperInitializationPhpCode, $node, $this);
			$initializationPhpCode .= $viewHelperInitializationPhpCode;
			if ($convertedViewHelperExecutionCode !== self::SHOULD_GENERATE_VIEWHELPER_INVOCATION) {
				return array(
					'initialization' => $initializationPhpCode,
					'execution' => $convertedViewHelperExecutionCode
				);
			}
		}

		// ViewHelper is not compilable, so we need to instanciate it directly and render it.
		$viewHelperVariableName = $this->variableName('viewHelper');

		$initializationPhpCode .= sprintf('%s = $self->getViewHelper(\'%s\', $renderingContext, \'%s\');', $viewHelperVariableName, $viewHelperVariableName, $node->getViewHelperClassName()) . chr(10);
		$initializationPhpCode .= sprintf('%s->setArguments(%s);', $viewHelperVariableName, $argumentsVariableName) . chr(10);
		$initializationPhpCode .= sprintf('%s->setRenderingContext($renderingContext);', $viewHelperVariableName) . chr(10);

		$initializationPhpCode .= sprintf('%s->setRenderChildrenClosure(%s);', $viewHelperVariableName, $renderChildrenClosureVariableName) . chr(10);

		$initializationPhpCode .= '// End of ViewHelper ' . $node->getViewHelperClassName() . chr(10);

		return array(
			'initialization' => $initializationPhpCode,
			'execution' => sprintf('%s->initializeArgumentsAndRender()', $viewHelperVariableName)
		);
	}

	/**
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode $node
	 * @return array
	 * @see convert()
	 */
	protected function convertObjectAccessorNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode $node) {
		return array(
			'initialization' => '',
			'execution' => sprintf('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), \'%s\', $renderingContext)', $node->getObjectPath())
		);
	}

	/**
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ArrayNode $node
	 * @return array
	 * @see convert()
	 */
	protected function convertArrayNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ArrayNode $node) {
		$initializationPhpCode = '// Rendering Array' . chr(10);
		$arrayVariableName = $this->variableName('array');

		$initializationPhpCode .= sprintf('%s = array();', $arrayVariableName) . chr(10);

		foreach ($node->getInternalArray() as $key => $value) {
			if ($value instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode) {
				$converted = $this->convert($value);
				$initializationPhpCode .= $converted['initialization'];
				$initializationPhpCode .= sprintf('%s[\'%s\'] = %s;', $arrayVariableName, $key, $converted['execution']) . chr(10);
			} elseif (is_numeric($value)) {
				// this case might happen for simple values
				$initializationPhpCode .= sprintf('%s[\'%s\'] = %s;', $arrayVariableName, $key, $value) . chr(10);
			} else {
				// this case might happen for simple values
				$initializationPhpCode .= sprintf('%s[\'%s\'] = \'%s\';', $arrayVariableName, $key, $this->escapeTextForUseInSingleQuotes($value)) . chr(10);
			}
		}
		return array(
			'initialization' => $initializationPhpCode,
			'execution' => $arrayVariableName
		);
	}

	/**
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node
	 * @return array
	 * @see convert()
	 */
	public function convertListOfSubNodes(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node) {
		switch (count($node->getChildNodes())) {
			case 0:
				return array(
					'initialization' => '',
					'execution' => 'NULL'
				);
			case 1:
				$converted = $this->convert(current($node->getChildNodes()));

				return $converted;
			default:
				$outputVariableName = $this->variableName('output');
				$initializationPhpCode = sprintf('%s = \'\';', $outputVariableName) . chr(10);

				foreach ($node->getChildNodes() as $childNode) {
					$converted = $this->convert($childNode);

					$initializationPhpCode .= $converted['initialization'] . chr(10);
					$initializationPhpCode .= sprintf('%s .= %s;', $outputVariableName, $converted['execution']) . chr(10);
				}

				return array(
					'initialization' => $initializationPhpCode,
					'execution' => $outputVariableName
				);
		}
	}

	/**
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\BooleanNode $node
	 * @return array
	 * @see convert()
	 */
	protected function convertBooleanNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\BooleanNode $node) {
		$initializationPhpCode = '// Rendering Boolean node' . chr(10);
		if ($node->getComparator() !== NULL) {
			$convertedLeftSide = $this->convert($node->getLeftSide());
			$convertedRightSide = $this->convert($node->getRightSide());

			return array(
				'initialization' => $initializationPhpCode . $convertedLeftSide['initialization'] . $convertedRightSide['initialization'],
				'execution' => sprintf('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\BooleanNode::evaluateComparator(\'%s\', %s, %s)', $node->getComparator(), $convertedLeftSide['execution'], $convertedRightSide['execution'])
			);
		} else {
			// simple case, no comparator.
			$converted = $this->convert($node->getSyntaxTreeNode());
			return array(
				'initialization' => $initializationPhpCode . $converted['initialization'],
				'execution' => sprintf('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\BooleanNode::convertToBoolean(%s)', $converted['execution'])
			);
		}
	}

	/**
	 * @param string $text
	 * @return string
	 */
	protected function escapeTextForUseInSingleQuotes($text) {
		return str_replace(array('\\', '\''), array('\\\\', '\\\''), $text);
	}

	/**
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node
	 * @return string
	 */
	public function wrapChildNodesInClosure(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $node) {
		$closure = '';
		$closure .= 'function() use ($renderingContext, $self) {' . chr(10);
		$convertedSubNodes = $this->convertListOfSubNodes($node);
		$closure .= $convertedSubNodes['initialization'];
		$closure .= sprintf('return %s;', $convertedSubNodes['execution']) . chr(10);
		$closure .= '}';
		return $closure;
	}

	/**
	 * Returns a unique variable name by appending a global index to the given prefix
	 *
	 * @param string $prefix
	 * @return string
	 */
	public function variableName($prefix) {
		return '$' . $prefix . $this->variableCounter++;
	}
}

?>