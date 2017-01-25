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
 * A node which is used inside boolean arguments
 */
class BooleanNode extends \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
{
    /**
     * List of comparators which are supported in the boolean expression language.
     *
     * Make sure that if one string is contained in one another, the longer
     * string is listed BEFORE the shorter one.
     * Example: put ">=" before ">"
     *
     * @var array
     */
    protected static $comparators = ['==', '!=', '%', '>=', '>', '<=', '<'];

    /**
     * A regular expression which checks the text nodes of a boolean expression.
     * Used to define how the regular expression language should look like.
     *
     * @var string
     */
    protected static $booleanExpressionTextNodeCheckerRegularExpression = '/
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
			|\'[^\'\\\\]* # single quoted string literals with possibly escaped single quotes
				(?:
					\\\\.      # escaped character
					[^\'\\\\]* # unrolled loop following Jeffrey E.F. Friedl
				)*\'
			|"[^"\\\\]*   # double quoted string literals with possibly escaped double quotes
				(?:
					\\\\.     # escaped character
					[^"\\\\]* # unrolled loop following Jeffrey E.F. Friedl
				)*"
		)*
		$/x';

    /**
     * Left side of the comparison
     *
     * @var \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
     */
    protected $leftSide;

    /**
     * Right side of the comparison
     *
     * @var \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
     */
    protected $rightSide;

    /**
     * The comparator. One element of self::$comparators. If NULL,
     * no comparator was found, and self::$syntaxTreeNode should
     * instead be evaluated.
     *
     * @var string
     */
    protected $comparator;

    /**
     * If no comparator was found, the syntax tree node should be
     * converted to boolean.
     *
     * @var \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
     */
    protected $syntaxTreeNode;

    /**
     * Constructor. Parses the syntax tree node and fills $this->leftSide, $this->rightSide,
     * $this->comparator and $this->syntaxTreeNode.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode
     * @throws \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    public function __construct(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode)
    {
        $childNodes = $syntaxTreeNode->getChildNodes();
        if (count($childNodes) > 3) {
            throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('A boolean expression has more than three parts.', 1244201848);
        } elseif (count($childNodes) === 0) {
            // In this case, we do not have child nodes; i.e. the current SyntaxTreeNode
            // is a text node with a literal comparison like "1 == 1"
            $childNodes = [$syntaxTreeNode];
        }

        $this->leftSide = new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode();
        $this->rightSide = new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode();
        $this->comparator = null;
        foreach ($childNodes as $childNode) {
            if ($childNode instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode && !preg_match(str_replace('COMPARATORS', implode('|', self::$comparators), self::$booleanExpressionTextNodeCheckerRegularExpression), $childNode->getText())) {
                // $childNode is text node, and no comparator found.
                $this->comparator = null;
                // skip loop and fall back to classical to boolean conversion.
                break;
            }

            if ($this->comparator !== null) {
                // comparator already set, we are evaluating the right side of the comparator
                $this->rightSide->addChildNode($childNode);
            } elseif ($childNode instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode
                && ($this->comparator = $this->getComparatorFromString($childNode->getText()))) {
                // comparator in current string segment
                $explodedString = explode($this->comparator, $childNode->getText());
                if (isset($explodedString[0]) && trim($explodedString[0]) !== '') {
                    $value = trim($explodedString[0]);
                    if (is_numeric($value)) {
                        $this->leftSide->addChildNode(new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NumericNode($value));
                    } else {
                        $this->leftSide->addChildNode(new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode(preg_replace('/(^[\'"]|[\'"]$)/', '', $value)));
                    }
                }
                if (isset($explodedString[1]) && trim($explodedString[1]) !== '') {
                    $value = trim($explodedString[1]);
                    if (is_numeric($value)) {
                        $this->rightSide->addChildNode(new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NumericNode($value));
                    } else {
                        $this->rightSide->addChildNode(new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode(preg_replace('/(^[\'"]|[\'"]$)/', '', $value)));
                    }
                }
            } else {
                // comparator not found yet, on the left side of the comparator
                $this->leftSide->addChildNode($childNode);
            }
        }

        if ($this->comparator === null) {
            // No Comparator found, we need to evaluate the given syntax tree node manually
            $this->syntaxTreeNode = $syntaxTreeNode;
        }
    }

    /**
     * @return string
     * @internal
     */
    public function getComparator()
    {
        return $this->comparator;
    }

    /**
     * @return \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
     * @internal
     */
    public function getSyntaxTreeNode()
    {
        return $this->syntaxTreeNode;
    }

    /**
     * @return \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
     * @internal
     */
    public function getLeftSide()
    {
        return $this->leftSide;
    }

    /**
     * @return \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
     * @internal
     */
    public function getRightSide()
    {
        return $this->rightSide;
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return bool the boolean value
     */
    public function evaluate(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
        if ($this->comparator !== null) {
            return self::evaluateComparator($this->comparator, $this->leftSide->evaluate($renderingContext), $this->rightSide->evaluate($renderingContext));
        } else {
            $value = $this->syntaxTreeNode->evaluate($renderingContext);
            return self::convertToBoolean($value);
        }
    }

    /**
     * Do the actual comparison. Compares $leftSide and $rightSide with $comparator and emits a boolean value.
     *
     * Some special rules apply:
     * - The == and != operators are comparing the Object Identity using === and !==, when one of the two
     *   operands are objects.
     * - For arithmetic comparisons (%, >, >=, <, <=), some special rules apply:
     *   - arrays are only comparable with arrays, else the comparison yields FALSE
     *   - objects are only comparable with objects, else the comparison yields FALSE
     *   - the comparison is FALSE when two types are not comparable according to the table
     *     "Comparison with various types" on http://php.net/manual/en/language.operators.comparison.php
     *
     * This function must be static public, as it is also directly called from cached templates.
     *
     * @param string $comparator
     * @param mixed $evaluatedLeftSide
     * @param mixed $evaluatedRightSide
     * @return bool TRUE if comparison of left and right side using the comparator emit TRUE, false otherwise
     * @throws \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    public static function evaluateComparator($comparator, $evaluatedLeftSide, $evaluatedRightSide)
    {
        switch ($comparator) {
            case '==':
                if (is_object($evaluatedLeftSide) || is_object($evaluatedRightSide)) {
                    return $evaluatedLeftSide === $evaluatedRightSide;
                } else {
                    return $evaluatedLeftSide == $evaluatedRightSide;
                }
            case '!=':
                if (is_object($evaluatedLeftSide) || is_object($evaluatedRightSide)) {
                    return $evaluatedLeftSide !== $evaluatedRightSide;
                } else {
                    return $evaluatedLeftSide != $evaluatedRightSide;
                }
            case '%':
                if (!self::isComparable($evaluatedLeftSide, $evaluatedRightSide)) {
                    return false;
                }
                return (bool)((int)$evaluatedLeftSide % (int)$evaluatedRightSide);
            case '>':
                if (!self::isComparable($evaluatedLeftSide, $evaluatedRightSide)) {
                    return false;
                }
                return $evaluatedLeftSide > $evaluatedRightSide;
            case '>=':
                if (!self::isComparable($evaluatedLeftSide, $evaluatedRightSide)) {
                    return false;
                }
                return $evaluatedLeftSide >= $evaluatedRightSide;
            case '<':
                if (!self::isComparable($evaluatedLeftSide, $evaluatedRightSide)) {
                    return false;
                }
                return $evaluatedLeftSide < $evaluatedRightSide;
            case '<=':
                if (!self::isComparable($evaluatedLeftSide, $evaluatedRightSide)) {
                    return false;
                }
                return $evaluatedLeftSide <= $evaluatedRightSide;
            default:
                throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('Comparator "' . $comparator . '" is not implemented.', 1244234398);
        }
    }

    /**
     * Checks whether two operands are comparable (based on their types). This implements
     * the "Comparison with various types" table from http://php.net/manual/en/language.operators.comparison.php,
     * only leaving out "array" with "anything" and "object" with anything; as we specify
     * that arrays and objects are incomparable with anything else than their type.
     *
     * @param mixed $evaluatedLeftSide
     * @param mixed $evaluatedRightSide
     * @return bool TRUE if the operands can be compared using arithmetic operators, FALSE otherwise.
     */
    protected static function isComparable($evaluatedLeftSide, $evaluatedRightSide)
    {
        if ((is_null($evaluatedLeftSide) || is_string($evaluatedLeftSide))
            && is_string($evaluatedRightSide)) {
            return true;
        }
        if (is_bool($evaluatedLeftSide) || is_null($evaluatedLeftSide)) {
            return true;
        }
        if (is_object($evaluatedLeftSide) && is_object($evaluatedRightSide)) {
            return true;
        }
        if ((is_string($evaluatedLeftSide) || is_resource($evaluatedLeftSide) || is_numeric($evaluatedLeftSide))
            && (is_string($evaluatedRightSide) || is_resource($evaluatedRightSide) || is_numeric($evaluatedRightSide))) {
            return true;
        }
        if (is_array($evaluatedLeftSide) && is_array($evaluatedRightSide)) {
            return true;
        }
        return false;
    }

    /**
     * Determine if there is a comparator inside $string, and if yes, returns it.
     *
     * @param string $string string to check for a comparator inside
     * @return string The comparator or NULL if none found.
     */
    protected function getComparatorFromString($string)
    {
        foreach (self::$comparators as $comparator) {
            if (strpos($string, $comparator) !== false) {
                return $comparator;
            }
        }
        return null;
    }

    /**
     * Convert argument strings to their equivalents. Needed to handle strings with a boolean meaning.
     *
     * Must be public and static as it is used from inside cached templates.
     *
     * @param mixed $value Value to be converted to boolean
     * @return bool
     */
    public static function convertToBoolean($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return !empty($value);
        }

        if (is_numeric($value)) {
            return $value != 0;
        }

        if (is_string($value)) {
            return !empty($value) && strtolower($value) !== 'false';
        }
        if (is_array($value) || (is_object($value) && $value instanceof \Countable)) {
            return (bool)count($value);
        }
        if (is_object($value)) {
            return true;
        }

        return false;
    }
}
