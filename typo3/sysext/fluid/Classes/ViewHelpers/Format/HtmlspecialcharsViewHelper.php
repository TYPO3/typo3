<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Format;

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
 * Applies htmlspecialchars() escaping to a value
 * @see http://www.php.net/manual/function.htmlspecialchars.php
 *
 * = Examples =
 *
 * <code title="default notation">
 * <f:format.htmlspecialchars>{text}</f:format.htmlspecialchars>
 * </code>
 * <output>
 * Text with & " ' < > * replaced by HTML entities (htmlspecialchars applied).
 * </output>
 *
 * <code title="inline notation">
 * {text -> f:format.htmlspecialchars(encoding: 'ISO-8859-1')}
 * </code>
 * <output>
 * Text with & " ' < > * replaced by HTML entities (htmlspecialchars applied).
 * </output>
 *
 * @api
 */
class HtmlspecialcharsViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Format\AbstractEncodingViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface {

	/**
	 * Disable the escaping interceptor because otherwise the child nodes would be escaped before this view helper
	 * can decode the text's entities.
	 *
	 * @var boolean
	 */
	protected $escapingInterceptorEnabled = FALSE;

	/**
	 * Escapes special characters with their escaped counterparts as needed using PHPs htmlspecialchars() function.
	 *
	 * @param string $value string to format
	 * @param boolean $keepQuotes if TRUE, single and double quotes won't be replaced (sets ENT_NOQUOTES flag)
	 * @param string $encoding
	 * @param boolean $doubleEncode If FALSE existing html entities won't be encoded, the default is to convert everything.
	 * @return string the altered string
	 * @see http://www.php.net/manual/function.htmlspecialchars.php
	 * @api
	 */
	public function render($value = NULL, $keepQuotes = FALSE, $encoding = NULL, $doubleEncode = TRUE) {
		if ($value === NULL) {
			$value = $this->renderChildren();
		}
		if (!is_string($value)) {
			return $value;
		}
		if ($encoding === NULL) {
			$encoding = $this->resolveDefaultEncoding();
		}
		$flags = $keepQuotes ? ENT_NOQUOTES : ENT_COMPAT;
		return htmlspecialchars($value, $flags, $encoding, $doubleEncode);
	}

	/**
	 * @param string $argumentsVariableName
	 * @param string $renderChildrenClosureVariableName
	 * @param string $initializationPhpCode
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode
	 * @param \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler
	 * @return string
	 */
	public function compile($argumentsVariableName, $renderChildrenClosureVariableName, &$initializationPhpCode, \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode, \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler) {
		$valueVariableName = $templateCompiler->variableName('value');
		$initializationPhpCode .= sprintf('%s = (%s[\'value\'] !== NULL ? %s[\'value\'] : %s());', $valueVariableName, $argumentsVariableName, $argumentsVariableName, $renderChildrenClosureVariableName) . chr(10);

		return sprintf('(!is_string(%s) ? %s : htmlspecialchars(%s, (%s[\'keepQuotes\'] ? ENT_NOQUOTES : ENT_COMPAT), (%s[\'encoding\'] !== NULL ? %s[\'encoding\'] : \\TYPO3\\CMS\\Fluid\\Core\\Compiler\\AbstractCompiledTemplate::resolveDefaultEncoding()), %s[\'doubleEncode\']))',
				$valueVariableName, $valueVariableName, $valueVariableName, $argumentsVariableName, $argumentsVariableName, $argumentsVariableName, $argumentsVariableName);
	}
}

?>