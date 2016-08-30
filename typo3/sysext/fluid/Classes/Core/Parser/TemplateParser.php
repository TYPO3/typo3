<?php
namespace TYPO3\CMS\Fluid\Core\Parser;

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
 * Template parser building up an object syntax tree
 */
class TemplateParser
{
    public static $SCAN_PATTERN_NAMESPACEDECLARATION = '/(?<!\\\\){namespace\\s*(?P<identifier>[a-zA-Z]+[a-zA-Z0-9]*)\\s*=\\s*(?P<phpNamespace>(?:[A-Za-z0-9\.]+|Tx)(?:LEGACY_NAMESPACE_SEPARATOR\\w+|FLUID_NAMESPACE_SEPARATOR\\w+)+)\\s*}/m';
    public static $SCAN_PATTERN_XMLNSDECLARATION = '/\sxmlns:(?P<identifier>.*?)="(?P<xmlNamespace>.*?)"/m';

    /**
     * The following two constants are used for tracking whether we are currently
     * parsing ViewHelper arguments or not. This is used to parse arrays only as
     * ViewHelper argument.
     */
    const CONTEXT_INSIDE_VIEWHELPER_ARGUMENTS = 1;
    const CONTEXT_OUTSIDE_VIEWHELPER_ARGUMENTS = 2;

    /**
     * This regular expression splits the input string at all dynamic tags, AND
     * on all <![CDATA[...]]> sections.
     */
    public static $SPLIT_PATTERN_TEMPLATE_DYNAMICTAGS = '/
		(
			(?: <\\/?                                      # Start dynamic tags
					(?:(?:NAMESPACE):[a-zA-Z0-9\\.]+)     # A tag consists of the namespace prefix and word characters
					(?:                                   # Begin tag arguments
						\\s*[a-zA-Z0-9:-]+                  # Argument Keys
						=                                 # =
						(?>                               # either... If we have found an argument, we will not back-track (That does the Atomic Bracket)
							"(?:\\\\"|[^"])*"              # a double-quoted string
							|\'(?:\\\\\'|[^\'])*\'        # or a single quoted string
						)\\s*                              #
					)*                                    # Tag arguments can be replaced many times.
				\\s*
				\\/?>                                      # Closing tag
			)
			|(?:                                          # Start match CDATA section
				<!\\[CDATA\\[.*?\\]\\]>
			)
		)/xs';

    /**
     * This regular expression scans if the input string is a ViewHelper tag
     */
    public static $SCAN_PATTERN_TEMPLATE_VIEWHELPERTAG = '/
		^<                                                # A Tag begins with <
		(?P<NamespaceIdentifier>NAMESPACE):               # Then comes the Namespace prefix followed by a :
		(?P<MethodIdentifier>                             # Now comes the Name of the ViewHelper
			[a-zA-Z0-9\\.]+
		)
		(?P<Attributes>                                   # Begin Tag Attributes
			(?:                                           # A tag might have multiple attributes
				\\s*
				[a-zA-Z0-9:-]+                             # The attribute name
				=                                         # =
				(?>                                       # either... # If we have found an argument, we will not back-track (That does the Atomic Bracket)
					"(?:\\\\"|[^"])*"                      # a double-quoted string
					|\'(?:\\\\\'|[^\'])*\'                # or a single quoted string
				)                                         #
				\\s*
			)*                                            # A tag might have multiple attributes
		)                                                 # End Tag Attributes
		\\s*
		(?P<Selfclosing>\\/?)                              # A tag might be selfclosing
		>$/x';

    /**
     * This regular expression scans if the input string is a closing ViewHelper
     * tag.
     */
    public static $SCAN_PATTERN_TEMPLATE_CLOSINGVIEWHELPERTAG = '/^<\\/(?P<NamespaceIdentifier>NAMESPACE):(?P<MethodIdentifier>[a-zA-Z0-9\\.]+)\\s*>$/';

    /**
     * This regular expression scans for HTML tags that have the attribute
     * data-namespace-typo3-fluid="true".
     * If this attribute is added to the HTML tag, the HTML tag will be removed
     * from the rendered output.
     */
    public static $SCAN_PATTERN_NAMESPACE_FLUID_HTML_TAG = '/<html\\s++[^>]*data-namespace-typo3-fluid="true"[^>]*>/m';

    /**
     * This regular expression is used to remove xmlns attributes that are used
     * to register ViewHelper namespaces.
     *
     * Replaces trailing empty spaces to avoid additional new lines that might be in the the tag.
     * It is therefore necessary to replace the pattern with space instead of empty string.
     *
     * Searches for multiple xmlns declarations after one another to only replace them with one space,
     * instead of one per xmlns definition.
     */
    public static $SCAN_PATTERN_REMOVE_VIEWHELPERS_XMLNSDECLARATIONS = '/(?:\\s*+xmlns:(?:%1$s)="[^"]*"\\s*+)++/m';

    /**
     * This regular expression splits the tag arguments into its parts
     */
    public static $SPLIT_PATTERN_TAGARGUMENTS = '/
		(?:                                              #
			\\s*                                          #
			(?P<Argument>                                # The attribute name
				[a-zA-Z0-9:-]+                            #
			)                                            #
			=                                            # =
			(?>                                          # If we have found an argument, we will not back-track (That does the Atomic Bracket)
				(?P<ValueQuoted>                         # either...
					(?:"(?:\\\\"|[^"])*")                 # a double-quoted string
					|(?:\'(?:\\\\\'|[^\'])*\')           # or a single quoted string
				)
			)\\s*
		)
		/xs';

    /**
     * This pattern detects CDATA sections and outputs the text between opening
     * and closing CDATA.
     */
    public static $SCAN_PATTERN_CDATA = '/^<!\\[CDATA\\[(.*?)\\]\\]>$/s';

    /**
     * Pattern which splits the shorthand syntax into different tokens. The
     * "shorthand syntax" is everything like {...}
     */
    public static $SPLIT_PATTERN_SHORTHANDSYNTAX = '/
		(
			{                                # Start of shorthand syntax
				(?:                          # Shorthand syntax is either composed of...
					[a-zA-Z0-9\\->_:,.()]     # Various characters
					|"(?:\\\\"|[^"])*"        # Double-quoted strings
					|\'(?:\\\\\'|[^\'])*\'   # Single-quoted strings
					|(?R)                    # Other shorthand syntaxes inside, albeit not in a quoted string
					|\\s+                     # Spaces
				)+
			}                                # End of shorthand syntax
		)/x';

    /**
     * Pattern which detects the object accessor syntax:
     * {object.some.value}, additionally it detects ViewHelpers like
     * {f:for(param1:bla)} and chaining like
     * {object.some.value->f:bla.blubb()->f:bla.blubb2()}
     *
     * THIS IS ALMOST THE SAME AS IN $SCAN_PATTERN_SHORTHANDSYNTAX_ARRAYS
     */
    public static $SCAN_PATTERN_SHORTHANDSYNTAX_OBJECTACCESSORS = '/
		^{                                                      # Start of shorthand syntax
			                                                # A shorthand syntax is either...
			(?P<Object>[a-zA-Z0-9\\-_.]*)                                     # ... an object accessor
			\\s*(?P<Delimiter>(?:->)?)\\s*

			(?P<ViewHelper>                                 # ... a ViewHelper
				[a-zA-Z0-9]+                                # Namespace prefix of ViewHelper (as in $SCAN_PATTERN_TEMPLATE_VIEWHELPERTAG)
				:
				[a-zA-Z0-9\\.]+                             # Method Identifier (as in $SCAN_PATTERN_TEMPLATE_VIEWHELPERTAG)
				\\(                                          # Opening parameter brackets of ViewHelper
					(?P<ViewHelperArguments>                # Start submatch for ViewHelper arguments. This is taken from $SCAN_PATTERN_SHORTHANDSYNTAX_ARRAYS
						(?:
							\\s*[a-zA-Z0-9\\-_]+                  # The keys of the array
							\\s*:\\s*                             # Key|Value delimiter :
							(?:                                 # Possible value options:
								"(?:\\\\"|[^"])*"                # Double qouoted string
								|\'(?:\\\\\'|[^\'])*\'          # Single quoted string
								|[a-zA-Z0-9\\-_.]+               # variable identifiers
								|{(?P>ViewHelperArguments)}     # Another sub-array
							)                                   # END possible value options
							\\s*,?                               # There might be a , to separate different parts of the array
						)*                                  # The above cycle is repeated for all array elements
					)                                       # End ViewHelper Arguments submatch
				\\)                                          # Closing parameter brackets of ViewHelper
			)?
			(?P<AdditionalViewHelpers>                      # There can be more than one ViewHelper chained, by adding more -> and the ViewHelper (recursively)
				(?:
					\\s*->\\s*
					(?P>ViewHelper)
				)*
			)
		}$/x';

    /**
     * THIS IS ALMOST THE SAME AS $SCAN_PATTERN_SHORTHANDSYNTAX_OBJECTACCESSORS
     */
    public static $SPLIT_PATTERN_SHORTHANDSYNTAX_VIEWHELPER = '/

		(?P<NamespaceIdentifier>[a-zA-Z0-9]+)       # Namespace prefix of ViewHelper (as in $SCAN_PATTERN_TEMPLATE_VIEWHELPERTAG)
		:
		(?P<MethodIdentifier>[a-zA-Z0-9\\.]+)
		\\(                                          # Opening parameter brackets of ViewHelper
			(?P<ViewHelperArguments>                # Start submatch for ViewHelper arguments. This is taken from $SCAN_PATTERN_SHORTHANDSYNTAX_ARRAYS
				(?:
					\\s*[a-zA-Z0-9\\-_]+                  # The keys of the array
					\\s*:\\s*                             # Key|Value delimiter :
					(?:                                 # Possible value options:
						"(?:\\\\"|[^"])*"                # Double qouoted string
						|\'(?:\\\\\'|[^\'])*\'          # Single quoted string
						|[a-zA-Z0-9\\-_.]+               # variable identifiers
						|{(?P>ViewHelperArguments)}     # Another sub-array
					)                                   # END possible value options
					\\s*,?                               # There might be a , to separate different parts of the array
				)*                                  # The above cycle is repeated for all array elements
			)                                       # End ViewHelper Arguments submatch
		\\)                                          # Closing parameter brackets of ViewHelper
		/x';

    /**
     * Pattern which detects the array/object syntax like in JavaScript, so it
     * detects strings like:
     * {object: value, object2: {nested: array}, object3: "Some string"}
     *
     * THIS IS ALMOST THE SAME AS IN SCAN_PATTERN_SHORTHANDSYNTAX_OBJECTACCESSORS
     */
    public static $SCAN_PATTERN_SHORTHANDSYNTAX_ARRAYS = '/^
		(?P<Recursion>                                  # Start the recursive part of the regular expression - describing the array syntax
			{                                           # Each array needs to start with {
				(?P<Array>                              # Start submatch
					(?:
						\\s*[a-zA-Z0-9\\-_]+              # The keys of the array
						\\s*:\\s*                         # Key|Value delimiter :
						(?:                             # Possible value options:
							"(?:\\\\"|[^"])*"            # Double qouoted string
							|\'(?:\\\\\'|[^\'])*\'      # Single quoted string
							|[a-zA-Z0-9\\-_.]+           # variable identifiers
							|(?P>Recursion)             # Another sub-array
						)                               # END possible value options
						\\s*,?                           # There might be a , to separate different parts of the array
					)*                                  # The above cycle is repeated for all array elements
				)                                       # End array submatch
			}                                           # Each array ends with }
		)$/x';

    /**
     * This pattern splits an array into its parts. It is quite similar to the
     * pattern above.
     */
    public static $SPLIT_PATTERN_SHORTHANDSYNTAX_ARRAY_PARTS = '/
		(?P<ArrayPart>                                             # Start submatch
			(?P<Key>[a-zA-Z0-9\\-_]+)                               # The keys of the array
			\\s*:\\s*                                                   # Key|Value delimiter :
			(?:                                                       # Possible value options:
				(?P<QuotedString>                                     # Quoted string
					(?:"(?:\\\\"|[^"])*")
					|(?:\'(?:\\\\\'|[^\'])*\')
				)
				|(?P<VariableIdentifier>[a-zA-Z][a-zA-Z0-9\\-_.]*)    # variable identifiers have to start with a letter
				|(?P<Number>[0-9.]+)                                  # Number
				|{\\s*(?P<Subarray>(?:(?P>ArrayPart)\\s*,?\\s*)+)\\s*}              # Another sub-array
			)                                                         # END possible value options
		)                                                          # End array part submatch
	/x';

    /**
     * This pattern detects the default xml namespace
     *
     */
    public static $SCAN_PATTERN_DEFAULT_XML_NAMESPACE = '/^http\:\/\/typo3\.org\/ns\/(?P<PhpNamespace>.+)$/s';

    /**
     * Namespace identifiers and their component name prefix (Associative array).
     * @var array
     */
    protected $namespaces = [
        'f' => 'TYPO3\\CMS\\Fluid\\ViewHelpers'
    ];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Parser\Configuration
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $viewHelperNameToImplementationClassNameRuntimeCache = [];

    /**
     * Constructor. Preprocesses the $SCAN_PATTERN_NAMESPACEDECLARATION by
     * inserting the correct namespace separator.
     */
    public function __construct()
    {
        self::$SCAN_PATTERN_NAMESPACEDECLARATION = str_replace(
            [
                'LEGACY_NAMESPACE_SEPARATOR',
                'FLUID_NAMESPACE_SEPARATOR'
            ],
            [
                preg_quote(\TYPO3\CMS\Fluid\Fluid::LEGACY_NAMESPACE_SEPARATOR),
                preg_quote(\TYPO3\CMS\Fluid\Fluid::NAMESPACE_SEPARATOR)
            ],
            self::$SCAN_PATTERN_NAMESPACEDECLARATION
        );
    }

    /**
     * Injects Fluid settings
     *
     * @param array $settings
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Set the configuration for the parser.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\Configuration $configuration
     * @return void
     */
    public function setConfiguration(\TYPO3\CMS\Fluid\Core\Parser\Configuration $configuration = null)
    {
        $this->configuration = $configuration;
    }

    /**
     * Parses a given template string and returns a parsed template object.
     *
     * The resulting ParsedTemplate can then be rendered by calling evaluate() on it.
     *
     * Normally, you should use a subclass of AbstractTemplateView instead of calling the
     * TemplateParser directly.
     *
     * @param string $templateString The template to parse as a string
     * @return \TYPO3\CMS\Fluid\Core\Parser\ParsedTemplateInterface Parsed template
     * @throws \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    public function parse($templateString)
    {
        if (!is_string($templateString)) {
            throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('Parse requires a template string as argument, ' . gettype($templateString) . ' given.', 1224237899);
        }
        $this->reset();

        $templateString = $this->extractNamespaceDefinitions($templateString);
        $splitTemplate = $this->splitTemplateAtDynamicTags($templateString);

        $parsingState = $this->buildObjectTree($splitTemplate, self::CONTEXT_OUTSIDE_VIEWHELPER_ARGUMENTS);

        $variableContainer = $parsingState->getVariableContainer();
        if ($variableContainer !== null && $variableContainer->exists('layoutName')) {
            $parsingState->setLayoutNameNode($variableContainer->get('layoutName'));
        }

        return $parsingState;
    }

    /**
     * Gets the namespace definitions found.
     *
     * @return array Namespace identifiers and their component name prefix
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * Resets the parser to its default values.
     *
     * @return void
     */
    protected function reset()
    {
        $this->namespaces = [
            'f' => 'TYPO3\\CMS\\Fluid\\ViewHelpers'
        ];
    }

    /**
     * Extracts namespace definitions out of the given template string and sets
     * $this->namespaces.
     *
     * @param string $templateString Template string to extract the namespaces from
     * @return string The updated template string without namespace declarations inside
     * @throws \TYPO3\CMS\Fluid\Core\Parser\Exception if a namespace can't be resolved or has been declared already
     */
    protected function extractNamespaceDefinitions($templateString)
    {
        $matches = [];
        $foundIdentifiers = [];
        preg_match_all(self::$SCAN_PATTERN_XMLNSDECLARATION, $templateString, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            // skip reserved "f" namespace identifier
            if ($match['identifier'] === 'f') {
                $foundIdentifiers[] = 'f';
                continue;
            }
            if (array_key_exists($match['identifier'], $this->namespaces)) {
                throw new \TYPO3\CMS\Fluid\Core\Parser\Exception(sprintf('Namespace identifier "%s" is already registered. Do not re-declare namespaces!', $match['identifier']), 1331135889);
            }
            if (isset($this->settings['namespaces'][$match['xmlNamespace']])) {
                $phpNamespace = $this->settings['namespaces'][$match['xmlNamespace']];
            } else {
                $matchedPhpNamespace = [];
                if (preg_match(self::$SCAN_PATTERN_DEFAULT_XML_NAMESPACE, $match['xmlNamespace'], $matchedPhpNamespace) === 0) {
                    continue;
                }
                $phpNamespace = str_replace('/', '\\', $matchedPhpNamespace['PhpNamespace']);
            }
            $foundIdentifiers[] = $match['identifier'];
            $this->namespaces[$match['identifier']] = $phpNamespace;
        }

        $templateString = $this->removeXmlnsViewHelperNamespaceDeclarations($templateString, $foundIdentifiers);

        $matches = [];
        preg_match_all(self::$SCAN_PATTERN_NAMESPACEDECLARATION, $templateString, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (array_key_exists($match['identifier'], $this->namespaces)) {
                throw new \TYPO3\CMS\Fluid\Core\Parser\Exception(sprintf('Namespace identifier "%s" is already registered. Do not re-declare namespaces!', $match['identifier']), 1224241246);
            }
            $this->namespaces[$match['identifier']] = $match['phpNamespace'];
        }
        if ($matches !== []) {
            $templateString = preg_replace(self::$SCAN_PATTERN_NAMESPACEDECLARATION, '', $templateString);
        }

        return $templateString;
    }

    /**
     * Removes html-tag (opening & closing) that is only used for xmlns definition
     * and xmlns attributes that register ViewHelpers on any tags
     *
     * @param string $templateString
     * @param array $foundIdentifiers
     * @return string
     */
    protected function removeXmlnsViewHelperNamespaceDeclarations($templateString, array $foundIdentifiers)
    {
        $foundHtmlTags = 0;
        $templateString = preg_replace(self::$SCAN_PATTERN_NAMESPACE_FLUID_HTML_TAG, '', $templateString, 1, $foundHtmlTags);
        if ($foundHtmlTags > 0) {
            $templateString = str_replace('</html>', '', $templateString);
        }

        if (!empty($foundIdentifiers)) {
            $foundIdentifiers = array_map(function ($foundIdentifier) {
                return preg_quote($foundIdentifier, '/');
            }, $foundIdentifiers);
            $foundIdentifiers = implode('|', $foundIdentifiers);

            // replaces the pattern with space because the pattern includes trailing spaces and consecutive xmlns ViewHelper defintions
            $templateString = preg_replace(
                sprintf(self::$SCAN_PATTERN_REMOVE_VIEWHELPERS_XMLNSDECLARATIONS, $foundIdentifiers),
                ' ',
                $templateString
            );
        }

        return $templateString;
    }

    /**
     * Splits the template string on all dynamic tags found.
     *
     * @param string $templateString Template string to split.
     * @return array Splitted template
     */
    protected function splitTemplateAtDynamicTags($templateString)
    {
        $regularExpression = $this->prepareTemplateRegularExpression(self::$SPLIT_PATTERN_TEMPLATE_DYNAMICTAGS);
        return preg_split($regularExpression, $templateString, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Build object tree from the split template
     *
     * @param array $splitTemplate The split template, so that every tag with a namespace declaration is already a separate array element.
     * @param int $context one of the CONTEXT_* constants, defining whether we are inside or outside of ViewHelper arguments currently.
     * @return \TYPO3\CMS\Fluid\Core\Parser\ParsingState
     * @throws \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    protected function buildObjectTree($splitTemplate, $context)
    {
        $regularExpression_openingViewHelperTag = $this->prepareTemplateRegularExpression(self::$SCAN_PATTERN_TEMPLATE_VIEWHELPERTAG);
        $regularExpression_closingViewHelperTag = $this->prepareTemplateRegularExpression(self::$SCAN_PATTERN_TEMPLATE_CLOSINGVIEWHELPERTAG);

        $state = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $rootNode = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode::class);
        $state->setRootNode($rootNode);
        $state->pushNodeToStack($rootNode);

        foreach ($splitTemplate as $templateElement) {
            $matchedVariables = [];
            if (preg_match(self::$SCAN_PATTERN_CDATA, $templateElement, $matchedVariables) > 0) {
                $this->textHandler($state, $matchedVariables[1]);
            } elseif (preg_match($regularExpression_openingViewHelperTag, $templateElement, $matchedVariables) > 0) {
                $this->openingViewHelperTagHandler($state, $matchedVariables['NamespaceIdentifier'], $matchedVariables['MethodIdentifier'], $matchedVariables['Attributes'], ($matchedVariables['Selfclosing'] !== ''));
            } elseif (preg_match($regularExpression_closingViewHelperTag, $templateElement, $matchedVariables) > 0) {
                $this->closingViewHelperTagHandler($state, $matchedVariables['NamespaceIdentifier'], $matchedVariables['MethodIdentifier']);
            } else {
                $this->textAndShorthandSyntaxHandler($state, $templateElement, $context);
            }
        }

        if ($state->countNodeStack() !== 1) {
            throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('Not all tags were closed!', 1238169398);
        }
        return $state;
    }

    /**
     * Handles an opening or self-closing view helper tag.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\ParsingState $state Current parsing state
     * @param string $namespaceIdentifier Namespace identifier - being looked up in $this->namespaces
     * @param string $methodIdentifier Method identifier
     * @param string $arguments Arguments string, not yet parsed
     * @param bool $selfclosing true, if the tag is a self-closing tag.
     * @return void
     */
    protected function openingViewHelperTagHandler(\TYPO3\CMS\Fluid\Core\Parser\ParsingState $state, $namespaceIdentifier, $methodIdentifier, $arguments, $selfclosing)
    {
        $argumentsObjectTree = $this->parseArguments($arguments);
        $this->initializeViewHelperAndAddItToStack($state, $namespaceIdentifier, $methodIdentifier, $argumentsObjectTree);

        if ($selfclosing) {
            $node = $state->popNodeFromStack();
            $this->callInterceptor($node, \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER, $state);
        }
    }

    /**
     * Initialize the given ViewHelper and adds it to the current node and to
     * the stack.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\ParsingState $state Current parsing state
     * @param string $namespaceIdentifier Namespace identifier - being looked up in $this->namespaces
     * @param string $methodIdentifier Method identifier
     * @param array $argumentsObjectTree Arguments object tree
     * @return void
     * @throws \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    protected function initializeViewHelperAndAddItToStack(\TYPO3\CMS\Fluid\Core\Parser\ParsingState $state, $namespaceIdentifier, $methodIdentifier, $argumentsObjectTree)
    {
        if (!array_key_exists($namespaceIdentifier, $this->namespaces)) {
            throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('Namespace could not be resolved. This exception should never be thrown!', 1224254792);
        }
        $viewHelper = $this->objectManager->get($this->resolveViewHelperName($namespaceIdentifier, $methodIdentifier));
        $this->viewHelperNameToImplementationClassNameRuntimeCache[$namespaceIdentifier][$methodIdentifier] = get_class($viewHelper);

        // The following three checks are only done *in an uncached template*, and not needed anymore in the cached version
        $expectedViewHelperArguments = $viewHelper->prepareArguments();
        $this->abortIfUnregisteredArgumentsExist($expectedViewHelperArguments, $argumentsObjectTree);
        $this->abortIfRequiredArgumentsAreMissing($expectedViewHelperArguments, $argumentsObjectTree);
        $this->rewriteBooleanNodesInArgumentsObjectTree($expectedViewHelperArguments, $argumentsObjectTree);

        $currentViewHelperNode = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class, $viewHelper, $argumentsObjectTree);

        $state->getNodeFromStack()->addChildNode($currentViewHelperNode);

        if ($viewHelper instanceof \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface && !($viewHelper instanceof \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface)) {
            $state->setCompilable(false);
        }

        // PostParse Facet
        if ($viewHelper instanceof \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\PostParseInterface) {
            $viewHelper::postParseEvent($currentViewHelperNode, $argumentsObjectTree, $state->getVariableContainer());
        }

        $this->callInterceptor($currentViewHelperNode, \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_OPENING_VIEWHELPER, $state);

        $state->pushNodeToStack($currentViewHelperNode);
    }

    /**
     * Throw an exception if there are arguments which were not registered
     * before.
     *
     * @param array $expectedArguments Array of \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition of all expected arguments
     * @param array $actualArguments Actual arguments
     * @throws \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    protected function abortIfUnregisteredArgumentsExist($expectedArguments, $actualArguments)
    {
        $expectedArgumentNames = [];
        foreach ($expectedArguments as $expectedArgument) {
            $expectedArgumentNames[] = $expectedArgument->getName();
        }

        foreach ($actualArguments as $argumentName => $_) {
            if (!in_array($argumentName, $expectedArgumentNames)) {
                throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('Argument "' . $argumentName . '" was not registered.', 1237823695);
            }
        }
    }

    /**
     * Throw an exception if required arguments are missing
     *
     * @param array $expectedArguments Array of \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition of all expected arguments
     * @param array $actualArguments Actual arguments
     * @throws \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    protected function abortIfRequiredArgumentsAreMissing($expectedArguments, $actualArguments)
    {
        $actualArgumentNames = array_keys($actualArguments);
        foreach ($expectedArguments as $expectedArgument) {
            if ($expectedArgument->isRequired() && !in_array($expectedArgument->getName(), $actualArgumentNames)) {
                throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('Required argument "' . $expectedArgument->getName() . '" was not supplied.', 1237823699);
            }
        }
    }

    /**
     * Wraps the argument tree, if a node is boolean, into a Boolean syntax tree node
     *
     * @param array $argumentDefinitions the argument definitions, key is the argument name, value is the ArgumentDefinition object
     * @param array $argumentsObjectTree the arguments syntax tree, key is the argument name, value is an AbstractNode
     * @return void
     */
    protected function rewriteBooleanNodesInArgumentsObjectTree($argumentDefinitions, &$argumentsObjectTree)
    {
        foreach ($argumentDefinitions as $argumentName => $argumentDefinition) {
            if ($argumentDefinition->getType() === 'boolean' && isset($argumentsObjectTree[$argumentName])) {
                $argumentsObjectTree[$argumentName] = new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\BooleanNode($argumentsObjectTree[$argumentName]);
            }
        }
    }

    /**
     * Resolve a viewhelper name.
     *
     * @param string $namespaceIdentifier Namespace identifier for the view helper.
     * @param string $methodIdentifier Method identifier, might be hierarchical like "link.url"
     * @return string The fully qualified class name of the viewhelper
     */
    protected function resolveViewHelperName($namespaceIdentifier, $methodIdentifier)
    {
        if (isset($this->viewHelperNameToImplementationClassNameRuntimeCache[$namespaceIdentifier][$methodIdentifier])) {
            $name = $this->viewHelperNameToImplementationClassNameRuntimeCache[$namespaceIdentifier][$methodIdentifier];
        } else {
            $explodedViewHelperName = explode('.', $methodIdentifier);
            $namespaceSeparator = strpos($this->namespaces[$namespaceIdentifier], \TYPO3\CMS\Fluid\Fluid::NAMESPACE_SEPARATOR) !== false ? \TYPO3\CMS\Fluid\Fluid::NAMESPACE_SEPARATOR : \TYPO3\CMS\Fluid\Fluid::LEGACY_NAMESPACE_SEPARATOR;
            if (count($explodedViewHelperName) > 1) {
                $className = implode($namespaceSeparator, array_map('ucfirst', $explodedViewHelperName));
            } else {
                $className = ucfirst($explodedViewHelperName[0]);
            }
            $className .= 'ViewHelper';
            $name = $this->namespaces[$namespaceIdentifier] . $namespaceSeparator . $className;
            $name = \TYPO3\CMS\Core\Core\ClassLoadingInformation::getClassNameForAlias($name);
            // The name isn't cached in viewHelperNameToImplementationClassNameRuntimeCache here because the
            // class could be overloaded by extbase object manager. Thus the cache is filled in
            // initializeViewHelperAndAddItToStack after getting the real object from the object manager.
        }
        return $name;
    }

    /**
     * Handles a closing view helper tag
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\ParsingState $state The current parsing state
     * @param string $namespaceIdentifier Namespace identifier for the closing tag.
     * @param string $methodIdentifier Method identifier.
     * @return void
     * @throws \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    protected function closingViewHelperTagHandler(\TYPO3\CMS\Fluid\Core\Parser\ParsingState $state, $namespaceIdentifier, $methodIdentifier)
    {
        if (!array_key_exists($namespaceIdentifier, $this->namespaces)) {
            throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('Namespace could not be resolved. This exception should never be thrown!', 1224256186);
        }
        $lastStackElement = $state->popNodeFromStack();
        if (!($lastStackElement instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode)) {
            throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('You closed a templating tag which you never opened!', 1224485838);
        }
        if ($lastStackElement->getViewHelperClassName() != $this->resolveViewHelperName($namespaceIdentifier, $methodIdentifier)) {
            throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('Templating tags not properly nested. Expected: ' . $lastStackElement->getViewHelperClassName() . '; Actual: ' . $this->resolveViewHelperName($namespaceIdentifier, $methodIdentifier), 1224485398);
        }
        $this->callInterceptor($lastStackElement, \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER, $state);
    }

    /**
     * Handles the appearance of an object accessor (like {posts.author.email}).
     * Creates a new instance of \TYPO3\CMS\Fluid\ObjectAccessorNode.
     *
     * Handles ViewHelpers as well which are in the shorthand syntax.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\ParsingState $state The current parsing state
     * @param string $objectAccessorString String which identifies which objects to fetch
     * @param string $delimiter
     * @param string $viewHelperString
     * @param string $additionalViewHelpersString
     * @return void
     */
    protected function objectAccessorHandler(\TYPO3\CMS\Fluid\Core\Parser\ParsingState $state, $objectAccessorString, $delimiter, $viewHelperString, $additionalViewHelpersString)
    {
        $viewHelperString .= $additionalViewHelpersString;
        $numberOfViewHelpers = 0;

        // The following post-processing handles a case when there is only a ViewHelper, and no Object Accessor.
        // Resolves bug #5107.
        if ($delimiter === '' && $viewHelperString !== '') {
            $viewHelperString = $objectAccessorString . $viewHelperString;
            $objectAccessorString = '';
        }

        // ViewHelpers
        $matches = [];
        if ($viewHelperString !== '' && preg_match_all(self::$SPLIT_PATTERN_SHORTHANDSYNTAX_VIEWHELPER, $viewHelperString, $matches, PREG_SET_ORDER) > 0) {
            // The last ViewHelper has to be added first for correct chaining.
            foreach (array_reverse($matches) as $singleMatch) {
                if ($singleMatch['ViewHelperArguments'] !== '') {
                    $arguments = $this->postProcessArgumentsForObjectAccessor(
                        $this->recursiveArrayHandler($singleMatch['ViewHelperArguments'])
                    );
                } else {
                    $arguments = [];
                }
                $this->initializeViewHelperAndAddItToStack($state, $singleMatch['NamespaceIdentifier'], $singleMatch['MethodIdentifier'], $arguments);
                $numberOfViewHelpers++;
            }
        }

        // Object Accessor
        if ($objectAccessorString !== '') {
            $node = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::class, $objectAccessorString);
            $this->callInterceptor($node, \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_OBJECTACCESSOR, $state);

            $state->getNodeFromStack()->addChildNode($node);
        }

        // Close ViewHelper Tags if needed.
        for ($i=0; $i<$numberOfViewHelpers; $i++) {
            $node = $state->popNodeFromStack();
            $this->callInterceptor($node, \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER, $state);
        }
    }

    /**
     * Call all interceptors registered for a given interception point.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface $node The syntax tree node which can be modified by the interceptors.
     * @param int $interceptionPoint the interception point. One of the \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_* constants.
     * @param \TYPO3\CMS\Fluid\Core\Parser\ParsingState $state the parsing state
     * @return void
     */
    protected function callInterceptor(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface &$node, $interceptionPoint, \TYPO3\CMS\Fluid\Core\Parser\ParsingState $state)
    {
        if ($this->configuration !== null) {
            // $this->configuration is UNSET inside the arguments of a ViewHelper.
            // That's why the interceptors are only called if the object accessor is not inside a ViewHelper Argument
            // This could be a problem if We have a ViewHelper as an argument to another ViewHelper, and an ObjectAccessor nested inside there.
            // @todo Clean up this.
            $interceptors = $this->configuration->getInterceptors($interceptionPoint);
            if (count($interceptors) > 0) {
                foreach ($interceptors as $interceptor) {
                    $node = $interceptor->process($node, $interceptionPoint, $state);
                }
            }
        }
    }

    /**
     * Post process the arguments for the ViewHelpers in the object accessor
     * syntax. We need to convert an array into an array of (only) nodes
     *
     * @param array $arguments The arguments to be processed
     * @return array the processed array
     * @todo This method should become superflous once the rest has been refactored, so that this code is not needed.
     */
    protected function postProcessArgumentsForObjectAccessor(array $arguments)
    {
        foreach ($arguments as $argumentName => $argumentValue) {
            if (!($argumentValue instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode)) {
                $arguments[$argumentName] = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode::class, (string)$argumentValue);
            }
        }
        return $arguments;
    }

    /**
     * Parse arguments of a given tag, and build up the Arguments Object Tree
     * for each argument.
     * Returns an associative array, where the key is the name of the argument,
     * and the value is a single Argument Object Tree.
     *
     * @param string $argumentsString All arguments as string
     * @return array An associative array of objects, where the key is the argument name.
     */
    protected function parseArguments($argumentsString)
    {
        $argumentsObjectTree = [];
        $matches = [];
        if (preg_match_all(self::$SPLIT_PATTERN_TAGARGUMENTS, $argumentsString, $matches, PREG_SET_ORDER) > 0) {
            $configurationBackup = $this->configuration;
            $this->configuration = null;
            foreach ($matches as $singleMatch) {
                $argument = $singleMatch['Argument'];
                $value = $this->unquoteString($singleMatch['ValueQuoted']);
                $argumentsObjectTree[$argument] = $this->buildArgumentObjectTree($value);
            }
            $this->configuration = $configurationBackup;
        }
        return $argumentsObjectTree;
    }

    /**
     * Build up an argument object tree for the string in $argumentString.
     * This builds up the tree for a single argument value.
     *
     * This method also does some performance optimizations, so in case
     * no { or < is found, then we just return a TextNode.
     *
     * @param string $argumentString
     * @return SyntaxTree\AbstractNode the corresponding argument object tree.
     */
    protected function buildArgumentObjectTree($argumentString)
    {
        if (strpos($argumentString, '{') === false && strpos($argumentString, '<') === false) {
            return $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode::class, $argumentString);
        }
        $splitArgument = $this->splitTemplateAtDynamicTags($argumentString);
        $rootNode = $this->buildObjectTree($splitArgument, self::CONTEXT_INSIDE_VIEWHELPER_ARGUMENTS)->getRootNode();
        return $rootNode;
    }

    /**
     * Removes escapings from a given argument string and trims the outermost
     * quotes.
     *
     * This method is meant as a helper for regular expression results.
     *
     * @param string $quotedValue Value to unquote
     * @return string Unquoted value
     */
    protected function unquoteString($quotedValue)
    {
        switch ($quotedValue[0]) {
            case '"':
                $value = str_replace('\\"', '"', preg_replace('/(^"|"$)/', '', $quotedValue));
            break;
            case "'":
                $value = str_replace("\\'", "'", preg_replace('/(^\'|\'$)/', '', $quotedValue));
            break;
            default:
                $value = $quotedValue;
        }
        return str_replace('\\\\', '\\', $value);
    }

    /**
     * Takes a regular expression template and replaces "NAMESPACE" with the
     * currently registered namespace identifiers. Returns a regular expression
     * which is ready to use.
     *
     * @param string $regularExpression Regular expression template
     * @return string Regular expression ready to be used
     */
    protected function prepareTemplateRegularExpression($regularExpression)
    {
        return str_replace('NAMESPACE', implode('|', array_keys($this->namespaces)), $regularExpression);
    }

    /**
     * Handler for everything which is not a ViewHelperNode.
     *
     * This includes Text, array syntax, and object accessor syntax.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\ParsingState $state Current parsing state
     * @param string $text Text to process
     * @param int $context one of the CONTEXT_* constants, defining whether we are inside or outside of ViewHelper arguments currently.
     * @return void
     */
    protected function textAndShorthandSyntaxHandler(\TYPO3\CMS\Fluid\Core\Parser\ParsingState $state, $text, $context)
    {
        $sections = preg_split($this->prepareTemplateRegularExpression(self::$SPLIT_PATTERN_SHORTHANDSYNTAX), $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        foreach ($sections as $section) {
            $matchedVariables = [];
            if (preg_match(self::$SCAN_PATTERN_SHORTHANDSYNTAX_OBJECTACCESSORS, $section, $matchedVariables) > 0) {
                $this->objectAccessorHandler($state, $matchedVariables['Object'], $matchedVariables['Delimiter'], isset($matchedVariables['ViewHelper']) ? $matchedVariables['ViewHelper'] : '', isset($matchedVariables['AdditionalViewHelpers']) ? $matchedVariables['AdditionalViewHelpers'] : '');
            } elseif ($context === self::CONTEXT_INSIDE_VIEWHELPER_ARGUMENTS && preg_match(self::$SCAN_PATTERN_SHORTHANDSYNTAX_ARRAYS, $section, $matchedVariables) > 0) {
                // We only match arrays if we are INSIDE viewhelper arguments
                $this->arrayHandler($state, $matchedVariables['Array']);
            } else {
                $this->textHandler($state, $section);
            }
        }
    }

    /**
     * Handler for array syntax. This creates the array object recursively and
     * adds it to the current node.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\ParsingState $state The current parsing state
     * @param string $arrayText The array as string.
     * @return void
     */
    protected function arrayHandler(\TYPO3\CMS\Fluid\Core\Parser\ParsingState $state, $arrayText)
    {
        $state->getNodeFromStack()->addChildNode(
            $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ArrayNode::class, $this->recursiveArrayHandler($arrayText))
        );
    }

    /**
     * Recursive function which takes the string representation of an array and
     * builds an object tree from it.
     *
     * Deals with the following value types:
     * - Numbers (Integers and Floats)
     * - Strings
     * - Variables
     * - sub-arrays
     *
     * @param string $arrayText Array text
     * @return SyntaxTree\ArrayNode the array node built up
     * @throws \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    protected function recursiveArrayHandler($arrayText)
    {
        $matches = [];
        if (preg_match_all(self::$SPLIT_PATTERN_SHORTHANDSYNTAX_ARRAY_PARTS, $arrayText, $matches, PREG_SET_ORDER) > 0) {
            $arrayToBuild = [];
            foreach ($matches as $singleMatch) {
                $arrayKey = $singleMatch['Key'];
                if (!empty($singleMatch['VariableIdentifier'])) {
                    $arrayToBuild[$arrayKey] = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::class, $singleMatch['VariableIdentifier']);
                } elseif (array_key_exists('Number', $singleMatch) && (!empty($singleMatch['Number']) || $singleMatch['Number'] === '0')) {
                    $arrayToBuild[$arrayKey] = floatval($singleMatch['Number']);
                } elseif ((array_key_exists('QuotedString', $singleMatch) && !empty($singleMatch['QuotedString']))) {
                    $argumentString = $this->unquoteString($singleMatch['QuotedString']);
                    $arrayToBuild[$arrayKey] = $this->buildArgumentObjectTree($argumentString);
                } elseif (array_key_exists('Subarray', $singleMatch) && !empty($singleMatch['Subarray'])) {
                    $arrayToBuild[$arrayKey] = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ArrayNode::class, $this->recursiveArrayHandler($singleMatch['Subarray']));
                } else {
                    throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('This exception should never be thrown, as the array value has to be of some type (Value given: "' . var_export($singleMatch, true) . '"). Please post your template to the bugtracker at forge.typo3.org.', 1225136013);
                }
            }
            return $arrayToBuild;
        } else {
            throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('This exception should never be thrown, there is most likely some error in the regular expressions. Please post your template to the bugtracker at forge.typo3.org.', 1225136014);
        }
    }

    /**
     * Text node handler
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\ParsingState $state
     * @param string $text
     * @return void
     */
    protected function textHandler(\TYPO3\CMS\Fluid\Core\Parser\ParsingState $state, $text)
    {
        $node = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode::class, $text);
        $this->callInterceptor($node, \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_TEXT, $state);

        $state->getNodeFromStack()->addChildNode($node);
    }
}
