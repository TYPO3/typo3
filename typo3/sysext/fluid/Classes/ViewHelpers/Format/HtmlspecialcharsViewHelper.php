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
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

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
class HtmlspecialcharsViewHelper extends AbstractEncodingViewHelper implements CompilableInterface
{
    /**
     * Disable the escaping interceptor because otherwise the child nodes would be escaped before this view helper
     * can decode the text's entities.
     *
     * @var bool
     */
    protected $escapingInterceptorEnabled = false;

    /**
     * Escapes special characters with their escaped counterparts as needed using PHPs htmlspecialchars() function.
     *
     * @param string $value string to format
     * @param bool $keepQuotes if TRUE, single and double quotes won't be replaced (sets ENT_NOQUOTES flag)
     * @param string $encoding
     * @param bool $doubleEncode If FALSE existing html entities won't be encoded, the default is to convert everything.
     * @return string the altered string
     * @see http://www.php.net/manual/function.htmlspecialchars.php
     * @api
     */
    public function render($value = null, $keepQuotes = false, $encoding = null, $doubleEncode = true)
    {
        if ($value === null) {
            $value = $this->renderChildren();
        }
        if (!is_string($value)) {
            return $value;
        }
        if ($encoding === null) {
            $encoding = self::resolveDefaultEncoding();
        }
        $flags = $keepQuotes ? ENT_NOQUOTES : ENT_COMPAT;
        return htmlspecialchars($value, $flags, $encoding, $doubleEncode);
    }

    /**
     * This ViewHelper is used a *lot* because it is used by the escape interceptor.
     * Therefore we render it to raw PHP code during compilation
     *
     * @param string $argumentsVariableName
     * @param string $renderChildrenClosureVariableName
     * @param string $initializationPhpCode
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode
     * @param \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler
     * @return string
     */
    public function compile($argumentsVariableName, $renderChildrenClosureVariableName, &$initializationPhpCode, \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode, \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler)
    {
        $valueVariableName = $templateCompiler->variableName('value');
        $initializationPhpCode .= sprintf('%s = (%s[\'value\'] !== NULL ? %s[\'value\'] : %s());', $valueVariableName, $argumentsVariableName, $argumentsVariableName, $renderChildrenClosureVariableName) . LF;

        return sprintf('(!is_string(%s) ? %s : htmlspecialchars(%s, (%s[\'keepQuotes\'] ? ENT_NOQUOTES : ENT_COMPAT), (%s[\'encoding\'] !== NULL ? %s[\'encoding\'] : \\TYPO3\\CMS\\Fluid\\Core\\Compiler\\AbstractCompiledTemplate::resolveDefaultEncoding()), %s[\'doubleEncode\']))',
                $valueVariableName, $valueVariableName, $valueVariableName, $argumentsVariableName, $argumentsVariableName, $argumentsVariableName, $argumentsVariableName);
    }
}
