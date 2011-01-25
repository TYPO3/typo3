<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Node which will call a ViewHelper associated with this node.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode extends Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode {

	/**
	 * Class name of view helper
	 * @var string
	 */
	protected $viewHelperClassName;

	/**
	 * Arguments of view helper - References to RootNodes.
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * The ViewHelper associated with this node
	 * @var Tx_Fluid_Core_ViewHelper_AbstractViewHelper
	 */
	protected $uninitializedViewHelper = NULL;

	/**
	 * A mapping RenderingContext -> ViewHelper to only re-initialize ViewHelpers
	 * when a context change occurs.
	 * @var Tx_Extbase_Persistence_ObjectStorage
	 */
	protected $viewHelpersByContext = NULL;

	/**
	 * List of comparators which are supported in the boolean expression language.
	 *
	 * Make sure that if one string is contained in one another, the longer
	 * string is listed BEFORE the shorter one.
	 * Example: put ">=" before ">"
	 * @var array of comparators
	 */
	static protected $comparators = array('==', '!=', '%', '>=', '>', '<=', '<');

	/**
	 * A regular expression which checks the text nodes of a boolean expression.
	 * Used to define how the regular expression language should look like.
	 * @var string Regular expression
	 */
	static protected $booleanExpressionTextNodeCheckerRegularExpression = '/
		^                 # Start with first input symbol
		(?:               # start repeat
			COMPARATORS   # We allow all comparators
			|\s*          # Arbitary spaces
			|-?           # Numbers, possibly with the "minus" symbol in front.
				[0-9]+    # some digits
				(?:       # and optionally a dot, followed by some more digits
					\\.
					[0-9]+
				)?
		)*
		$/x';

	/**
	 * Constructor.
	 *
	 * @param Tx_Fluid_Core_ViewHelper_AbstractViewHelper $viewHelper The view helper
	 * @param array $arguments Arguments of view helper - each value is a RootNode.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(Tx_Fluid_Core_ViewHelper_AbstractViewHelper $viewHelper, array $arguments) {
		$this->uninitializedViewHelper = $viewHelper;
		$this->viewHelpersByContext = t3lib_div::makeInstance('Tx_Extbase_Persistence_ObjectStorage');
		$this->arguments = $arguments;

		if (FALSE /*FIXME*/) {
			$this->viewHelperClassName = $this->uninitializedViewHelper->FLOW3_AOP_Proxy_getProxyTargetClassName();
		} else {
			$this->viewHelperClassName = get_class($this->uninitializedViewHelper);
		}
	}

	/**
	 * Returns the attached (but still uninitialized) ViewHelper for this ViewHelperNode.
	 * We need this method because sometimes Interceptors need to ask some information from the ViewHelper.
	 *
	 * @return Tx_Fluid_Core_ViewHelper_AbstractViewHelper the attached ViewHelper, if it is initialized
	 */
	public function getUninitializedViewHelper() {
		return $this->uninitializedViewHelper;
	}

	/**
	 * Get class name of view helper
	 *
	 * @return string Class Name of associated view helper
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getViewHelperClassName() {
		return $this->viewHelperClassName;
	}

	/**
	 * Call the view helper associated with this object.
	 *
	 * First, it evaluates the arguments of the view helper.
	 *
	 * If the view helper implements Tx_Fluid_Core_ViewHelper_Facets_ChildNodeAccessInterface,
	 * it calls setChildNodes(array childNodes) on the view helper.
	 *
	 * Afterwards, checks that the view helper did not leave a variable lying around.
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext
	 * @return object evaluated node after the view helper has been called.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo check recreation of viewhelper when revisiting caching
	 */
	public function evaluate(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext) {
		$objectManager = $renderingContext->getObjectManager();
		$contextVariables = $renderingContext->getTemplateVariableContainer()->getAllIdentifiers();

		if ($this->viewHelpersByContext->contains($renderingContext)) {
			$viewHelper = $this->viewHelpersByContext[$renderingContext];
		} else {
			$viewHelper = clone $this->uninitializedViewHelper;
			$this->viewHelpersByContext->attach($renderingContext, $viewHelper);
		}

		$evaluatedArguments = array();
		$renderMethodParameters = array();
 		if (count($viewHelper->prepareArguments())) {
 			foreach ($viewHelper->prepareArguments() as $argumentName => $argumentDefinition) {
				if (isset($this->arguments[$argumentName])) {
					$argumentValue = $this->arguments[$argumentName];
					$evaluatedArguments[$argumentName] = $this->convertArgumentValue($argumentValue, $argumentDefinition->getType(), $renderingContext);
				} else {
					$evaluatedArguments[$argumentName] = $argumentDefinition->getDefaultValue();
				}
				if ($argumentDefinition->isMethodParameter()) {
					$renderMethodParameters[$argumentName] = $evaluatedArguments[$argumentName];
				}
			}
		}

		$viewHelperArguments = $objectManager->create('Tx_Fluid_Core_ViewHelper_Arguments', $evaluatedArguments);
		$viewHelper->setArguments($viewHelperArguments);
		$viewHelper->setTemplateVariableContainer($renderingContext->getTemplateVariableContainer());
		if ($renderingContext->getControllerContext() !== NULL) {
			$viewHelper->setControllerContext($renderingContext->getControllerContext());
		}
		$viewHelper->setViewHelperVariableContainer($renderingContext->getViewHelperVariableContainer());
		$viewHelper->setViewHelperNode($this);
		$viewHelper->setRenderingContext($renderingContext);

		if ($viewHelper instanceof Tx_Fluid_Core_ViewHelper_Facets_ChildNodeAccessInterface) {
			$viewHelper->setChildNodes($this->childNodes);
		}

		$output = $viewHelper->initializeArgumentsAndRender($renderMethodParameters);

		return $output;
	}

	/**
	 * Convert argument strings to their equivalents. Needed to handle strings with a boolean meaning.
	 *
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $syntaxTreeNode Value to be converted
	 * @param string $type Target type
	 * @return mixed New value
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function convertArgumentValue(Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $syntaxTreeNode, $type, Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext) {
		if ($type === 'boolean') {
			return $this->evaluateBooleanExpression($syntaxTreeNode, $renderingContext);
		}
		return $syntaxTreeNode->evaluate($renderingContext);
	}

	/**
	 * Convert boolean expression syntax tree to some meaningful value.
	 * The expression is available as the SyntaxTree of the argument.
	 *
	 * We currently only support expressions of the form:
	 * XX Comparator YY
	 * Where XX and YY can be either:
	 * - a number
	 * - an Object accessor
	 * - an array
	 * - a ViewHelper
	 *
	 * and comparator must be one of the above.
	 *
	 * In case no comparator is found, the fallback of "convertToBoolean" is used.
	 *
	 *
	 * Internal work:
	 * First, we loop through the child syntaxtree nodes, to fill the left side of the comparator,
	 * the right side of the comparator, and the comparator itself.
	 * Then, we evaluate the obtained left and right side using the given comparator. This is done inside the evaluateComparator method.
	 *
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $syntaxTreeNode Value to be converted
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext
	 * @return boolean Evaluated value
	 * @throws Tx_Fluid_Core_Parser_Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function evaluateBooleanExpression(Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $syntaxTreeNode, Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext) {
		$childNodes = $syntaxTreeNode->getChildNodes();
		if (count($childNodes) > 3) {
			throw new Tx_Fluid_Core_Parser_Exception('The expression "' . $syntaxTreeNode->evaluate($renderingContext) . '" has more than tree parts.', 1244201848);
		}

		$leftSide = NULL;
		$rightSide = NULL;
		$comparator = NULL;
		foreach ($childNodes as $childNode) {
			if ($childNode instanceof Tx_Fluid_Core_Parser_SyntaxTree_TextNode && !preg_match(str_replace('COMPARATORS', implode('|', self::$comparators), self::$booleanExpressionTextNodeCheckerRegularExpression), $childNode->evaluate($renderingContext))) {
				$comparator = NULL;
					// skip loop and fall back to classical to boolean conversion.
				break;
			}

			if ($comparator !== NULL) {
					// comparator already set, we are evaluating the right side of the comparator
				if ($rightSide === NULL) {
					$rightSide = $childNode->evaluate($renderingContext);
				} else {
					$rightSide .= $childNode->evaluate($renderingContext);
				}
			} elseif ($childNode instanceof Tx_Fluid_Core_Parser_SyntaxTree_TextNode
				&& ($comparator = $this->getComparatorFromString($childNode->evaluate($renderingContext)))) {
					// comparator in current string segment
				$explodedString = explode($comparator, $childNode->evaluate($renderingContext));
				if (isset($explodedString[0]) && trim($explodedString[0]) !== '') {
					$leftSide .= trim($explodedString[0]);
				}
				if (isset($explodedString[1]) && trim($explodedString[1]) !== '') {
					$rightSide .= trim($explodedString[1]);
				}
			} else {
					// comparator not found yet, on the left side of the comparator
				if ($leftSide === NULL) {
					$leftSide = $childNode->evaluate($renderingContext);
				} else {
					$leftSide .= $childNode->evaluate($renderingContext);
				}
			}
		}

		if ($comparator !== NULL) {
			return $this->evaluateComparator($comparator, $leftSide, $rightSide);
		} else {
			return $this->convertToBoolean($syntaxTreeNode->evaluate($renderingContext));
		}
	}

	/**
	 * Do the actual comparison. Compares $leftSide and $rightSide with $comparator and emits a boolean value
	 *
	 * @param string $comparator One of self::$comparators
	 * @param mixed $leftSide Left side to compare
	 * @param mixed $rightSide Right side to compare
	 * @return boolean TRUE if comparison of left and right side using the comparator emit TRUE, false otherwise
	 * @throws Tx_Fluid_Core_Parser_Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function evaluateComparator($comparator, $leftSide, $rightSide) {
		switch ($comparator) {
			case '==':
				if (is_object($leftSide) && is_object($rightSide)) {
					return ($leftSide === $rightSide);
				}
				return ($leftSide == $rightSide);
				break;
			case '!=':
				if (is_object($leftSide) && is_object($rightSide)) {
					return ($leftSide !== $rightSide);
				}
				return ($leftSide != $rightSide);
				break;
			case '%':
				return (boolean)((int)$leftSide % (int)$rightSide);
			case '>':
				return ($leftSide > $rightSide);
			case '>=':
				return ($leftSide >= $rightSide);
			case '<':
				return ($leftSide < $rightSide);
			case '<=':
				return ($leftSide <= $rightSide);
			default:
				throw new Tx_Fluid_Core_Parser_Exception('Comparator "' . $comparator . '" is not implemented.', 1244234398);
		}
	}

	/**
	 * Determine if there is a comparator inside $string, and if yes, returns it.
	 *
	 * @param string $string string to check for a comparator inside
	 * @return string The comparator or NULL if none found.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getComparatorFromString($string) {
		foreach (self::$comparators as $comparator) {
			if (strpos($string, $comparator) !== FALSE) {
				return $comparator;
			}
		}

		return NULL;
	}

	/**
	 * Convert argument strings to their equivalents. Needed to handle strings with a boolean meaning.
	 *
	 * @param mixed $value Value to be converted to boolean
	 * @return boolean
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @todo this should be moved to another class
	 */
	protected function convertToBoolean($value) {
		if (is_bool($value)) {
			return $value;
		}
		if (is_numeric($value)) {
			return $value > 0;
		}
		if (is_string($value)) {
			return (!empty($value) && strtolower($value) !== 'false');
		}
		if (is_array($value) || (is_object($value) && $value instanceof Countable)) {
			return count($value) > 0;
		}
		if (is_object($value)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Clean up for serializing.
	 *
	 * @return array
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __sleep() {
		return array('viewHelperClassName', 'arguments', 'childNodes');
	}
}

?>