
.. include:: ../../Includes.txt

=================================================================
Feature: #69863 - Use new standalone Fluid as composer dependency
=================================================================

See :issue:`69863`

Description
===========

The Fluid rendering engine of TYPO3 CMS is replaced by the standalone capable Fluid which is now included as composer dependency.
The old Fluid extension is converted to a so-called Fluid adapter which allows TYPO3 CMS to use standalone Fluid with the many
new features this facilitates.


Impact
======

New features/capabilities have been added in nearly all areas of Fluid. Most importantly: several of the Fluid components which
in the past were completely internal and impossible to replace, are now easy to replace and have been fitted with a public API.
Unless noted otherwise each feature below is part of such a new API component which you can replace. This gives you unprecedented
control over almost all of Fluid's behaviours in a way that can be controlled via each View instance, for example from within a
controller's initializeView method.

Developers working with just the template side of Fluid will not notice many differences. Those that they will notice are, for
the most part, hidden away beneath a compatibility flag that is set via the View, allowing each extension to opt-in to the
extended capabilities or to stick with the legacy behaviour of Fluid. The new features that relate to these new behaviours are:

RenderingContext
----------------

The most important new piece of public API is the RenderingContext. The previously internal-only RenderingContext used by Fluid
has been expanded to be responsible for a vital new Fluid feature: implementation provisioning. This enables a developer to
change a range of classes Fluid uses for parsing, resolving, caching etc. by either including a custom RenderingContext or
manipulating the default RenderingContext by public methods, a developer is able to change a range of classes Fluid uses for
parsing, resolving, caching etc.

Each component which can be replaced this way is described in further detail below.

Impact for template developers
==============================

The following behaviours can all be controlled by manipulating the RenderingContext. By default, none of them are enabled - but
calling a simple method (via your View instance) allows you to enable them:

.. code-block:: php

	$view->getRenderingContext()->setLegacyMode(false);

Doing so causes the RenderingContext to deliver different implementations, which simply means that in addition to what you
already know Fluid to be capable of (variable access, inline syntax etc.) you gain access to the following features:

ExpressionNodes
---------------

Expression Nodes are a new type of Fluid syntax structures which all share a common trait: they only work inside the curly braces
previously only used by variable accessing and inline syntax of ViewHelpers. You can define the exact collection of
ExpressionNodes that are active for your rendering process, via the View instance:

.. code-block:: php

	$view->getRenderingContext()->setExpressionNodeTypes(array(
		'Class\Number\One',
		'Class\Number\Two'
	));

When added to this collection these Expression Node types allow new syntaxes such as `{myVariable + 1}` or
`{myArrayLikeObject as array}`. When the legacy mode toggle is set to `false` this will enable the following
expression types:

1. CastingExpressionNode - this type allows casting a variable to certain types, for example to guarantee an integer or a
   boolean. It is used simply with an `as` keyword: `{myStringVariable as boolean}`, `{myBooleanVariable as integer}` and
   so on. Attempting to cast a variable to an incompatible type causes a standard Fluid error.
2. MathExpressionNode - this type allows basic mathematical operations on variables, for example `{myNumber + 1}`,
   `{myPercent / 100}`, `{myNumber * 100}` and so on. An impossible expression returns an empty output.
3. TernaryExpressionNode - this type allows an inline ternary condition which only operates on variables. The use case is "if
   this variable then use that variable else use another variable". It is used as
   `{myToggleVariable ? myThenVariable : myElseVariable}`. Note that it does not support any nested expressions, inline
   ViewHelper syntaxes or similar inside it - it must be used only with standard variables as input.

Developers can add their own additional ExpressionNodeTypes. Each one consists of a pattern to be matched and methods dictated
by an interface to process the matches - any existing ExpressionNode type can be used as reference.

Namespaces are extensible
-------------------------

Fluid now allows each namespace alias (for example `f:`) to be extended by adding to it additional PHP namespaces that are
also checked for the presence of ViewHelper classes. This is what allows TYPO3 CMS to transparently add just the ViewHelpers that
are unique to TYPO3 CMS and let Fluid add the rest. It also means that developers can override individual ViewHelpers with custom
versions and have their ViewHelpers called when the `f:` namespace is used.

This change also implies that namespaces are no longer monadic - any time you use `{namespace f=My\Extension\ViewHelpers}` you
will no longer receive an error with "namespace already registered". Fluid will instead add this PHP namespace and look for
ViewHelpers there as well. Additional namespaces are checked from the bottom up, allowing the additional namespaces to override
ViewHelper classes by placing them in the same scope (e.g. `f:format.nl2br` can be overridden with
`My\Extension\ViewHelpers\Format\Nl2brViewHelper` given the namespace registration example above.

The behaviour is used both for legacy namespace registration in curly braces and the modern `xmlns` approach using a
container HTML tag.

Rendering using f:render
------------------------

This specific ViewHelper is fundamentally different in the standalone Fluid version - in the default (current) usage scenarios
it behaves completely like you are used to, but has been fitted with some major impact features that you can use whenever you
want to, in any template.

There are two specific changes both documented in their respective commits:

1. Default content (when section/partial is missing) now possible - https://github.com/TYPO3Fluid/Fluid/commit/cd67f9d974bc489058bde1c4272b480eb349da09
2. Tag content of `f:render` can now be passed as a variable to the section/partial being rendered (essentially becoming a
   wrapping/block strategy) - https://github.com/TYPO3Fluid/Fluid/commit/454121cba81baed4e3fe526412ff3e14f7c499a9

All TagBasedViewHelpers natively support data- prefixed attributes
------------------------------------------------------------------

Simply put - any TagBasedViewHelper can now receive `data-` prefixed attributes without requiring those attributes to be
declared by the ViewHelper. Any suffix can be used as long as the prefix is `data-`.

Complex conditional statements
------------------------------

As a forced new feature - which is backwards compatible - Fluid now supports any degree of complex conditional statements with
nesting and grouping:

.. code-block:: xml

	<f:if condition="({variableOne} && {variableTwo}) || {variableThree} || {variableFour}">
		// Done if both variable one and two evaluate to true, or if either variable three or four do.
	</f:if>

In addition, `f:else` has been fitted with an "elseif"-like behavior:

.. code-block:: xml

	<f:if condition="{variableOne}">
		<f:then>Do this</f:then>
		<f:else if="{variableTwo}">Do this instead if variable two evals true</f:else>
		<f:else if="{variableThree}">Or do this if variable three evals true</f:else>
		<f:else>Or do this if nothing above is true</f:else>
	</f:if>

Dynamic variable name parts
---------------------------

Another forced new feature, likewise backwards compatible, is the added ability to use sub-variable references when accessing
your variables. Consider the following Fluid template variables array:

.. code-block:: php

	$mykey = 'foo'; // or 'bar', set by any source
	$view->assign('data', ['foo' => 1, 'bar' => 2]);
	$view->assign('key', $mykey);

With the following Fluid template:

.. code-block:: xml

	You chose: {data.{key}}.
	(output: "1" if key is "foo" or "2" if key is "bar")

The same approach can also be used to generate dynamic parts of a string variable name:

.. code-block:: php

	$mydynamicpart = 'First'; // or 'Second', set by any source
	$view->assign('myFirstVariable', 1);
	$view->assign('mySecondVariable', 2);
	$view->assign('which', $mydynamicpart);

With the following Fluid template:

.. code-block:: xml

	You chose: {my{which}Variable}.
	(output: "1" if which is "First" or "2" if which is "Second")

This syntax can be used anywhere a variable is referenced, with one exception: variables passed as pure variable accessors cannot
contain dynamic parts, e.g. the following will **NOT** work:

.. code-block:: xml

	{f:if(condition: my{which}Variable, then: 'this', else: 'that')}

Whereas the following **will** work because the variables are accessed wrapped in a text node:

.. code-block:: xml

	{f:if(condition: '{my{which}Variable}', then: 'this', else: 'that')}

In other words: unless your outer variable reference is enclosed with curly braces, Fluid does not detect that you are
referencing a dynamic variable and will instead assume you meant a variable actually named `my{which}Variable` which was added
as `$view->assign('my{which}Variable', 'value')`.

New ViewHelpers
---------------

A few new ViewHelpers have been added to the collection as part of standalone Fluid and as such are also available in TYPO3 from now on:

* `f:or` which is a shorter way to write (chained) conditions. It supports syntax like
  `{variableOne -> f:or(alternative: variableTwo) -> f:or(alternative: variableThree)}` which checks each variable and outputs
  the first one that's not empty.
* `f:spaceless` which can be used in tag-mode around template code to eliminate redundant whitespace and blank lines for
  example caused by indenting ViewHelper usages.

Improved error reporting
------------------------

Syntax errors or problems with required arguments or incorrect argument types will now be reported with line number and template
code example from the line that fails. Any ViewHelper Exception is turned into this improved error type by converting it to a
special syntax error and attaching the original Exception to it.

An example error could be:

``TYPO3Fluid\Fluid\Core\Parser\Exception: Fluid parse error in template Default_action_Default_1cb8dc11e29962882f629f79c0b9113ff33d6219,
line 11 at character 3. Error: The ViewHelper "<f:serender>" could not be resolved. Based on your spelling, the system would load
the class "TYPO3Fluid\Fluid\ViewHelpers\SerenderViewHelper", however this class does not exist. (1407060572). Template code:
<f:serender section="Foo" optional="1">``. A stack trace is still included if TYPO3 does not run in Production context.

Impact for extension developers
===============================

Extension developers are affected mainly by gaining access to a range of new APIs that control Fluid's behavior. These new APIs
can all be accessed via the RenderingContext which is available in Views and ViewHelpers (also when compiled). Developers can
provide custom implementations or manipulate the standard implementations by retrieving each API through the RenderingContext
and using methods of those.

There are no significant changes to best practices and the ViewHelper API (which you use when creating custom ViewHelpers)
remains largely untouched. The most notable change is that `$this->renderingContext` in ViewHelpers and Views now allows direct
access to on-the-fly changes in Fluid's behavior.

RenderingContext as implementation API
--------------------------------------

Rather than just being a simple context which hangs on to variables, the RenderingContext has been given a completely new and
even more vital role in Fluid - it is now the API for delivering custom implementations for a range of features that until now
were only possible to achieve via means like XCLASSing. A RenderingContext now delivers the following components:

* The VariableProvider (previously known as TemplateVariableContainer, see below) used in rendering
* The ViewHelperVariableContainer (already known) used in rendering
* The ViewHelperResolver (new pattern) responsible for handling namespaces and resolving/creating ViewHelper instances
  and arguments
* The ViewHelperInvoker (new pattern) responsible for calling ViewHelpers (circumvented when ViewHelpers implement a custom
  `compile()` method)
* The TemplatePaths (new pattern) which is a template file resolving class that now contains resolving methods previously found
  on the View itself
* The TemplateParser (already known) which is responsible for parsing the template and creating a ParsedTemplate
* The TemplateCompiler (already known) which is responsible for converting a ParsedTemplate to a native PHP class
* The FluidCache (new pattern) which is a custom caching implementation compatible with TYPO3 caching frontends/backends
  storing PHP files
* An array of ExpressionNodeTypes (class names, new pattern) - see description of those above
* An array of TemplateProcessors (instances, new pattern) which pre-process template source code before it gets handed off to the
  TemplateParser, allowing things like extracting registered namespaces in custom ways.
* The controller name, if one applies to the context
* The controller action name, if one applies to the context
* And for TYPO3 CMS only, the Extbase ControllerContext (which is as it has always been; contains a Request etc.).

All (!) of which can be replaced with custom implementations and all of which are accessible through View and ViewHelpers alike.
Just a few of the capabilities you gain:

* You can create custom VariableProvider implementations which retrieve variables in new ways from new sources - Fluid itself now
  includes a JSON-based VariableProvider as well as a ChainedVariableProvider which allows "plugging" multiple variable sources.
* You can create a custom ViewHelperResolver implementation which can do things like automatically register namespaces that are
  always available or change the way ViewHelper classes are detected, instantiated, how arguments are detected, and more.
* You can create a custom ViewHelperInvoker implementation which calls ViewHelpers in new ways - combined with a custom
  ViewHelperResolver this can for example allow non-ViewHelper classes to be used as if they actually were ViewHelpers.
* You can create custom TemplatePaths implementations which for example read template sources not from the local file system but
  from database, remote storage, zip files, whatever you desire.
* You can replace the TemplateParser itself (but be careful if you do, obviously). There are no current use cases for this, but
  the possibility exists.
* You can replace the TemplateCompiler (be careful here too). No use case exists but this could be used to compile Fluid
  templates to other things than PHP.
* You can replace the Cache implementation - for example to cache compiled Fluid templates in memcache or a distributed cache
  accessible by PHP opcache.
* You can change which Expression Node types are possible to use in templates rendered with your context, for example disabling
  ternary expressions or adding a custom type of expression of your own.
* You can change which TemplateProcessors will be used to process templates when rendered with your context, to do whatever you
  like - transform, analyse and so on the template source.

All of these parts are possible to replace via the provided RenderingContext - you don't necessarily have to create your own -
but when creating multiple implementations it is often easier to combine those in a custom RenderingContext and just provide
that for your View.

But perhaps most importantly, because all of these components are contained in the RenderingContext which is available to Views
and ViewHelpers alike (also once compiled!), it becomes possible for your View or ViewHelpers to actually interact with the Fluid
environment in powerful ways. To illustrate how powerful, you could create a single ViewHelper which: manipulates the Expression
Node types usable in its tag content, changes the paths used to resolve Partials, registers a number of other ViewHelper
namespaces, changes the variable source to be a JSON file or URL and adds a pre-processing class that triggers on every template
source read from within the ViewHelper's tag contents, to strip some undesired namespace from third party Partials. And it could
restore the context afterwards so that all of this only applies inside that ViewHelper's tag content.

ViewHelper namespaces can be extended also from PHP
---------------------------------------------------

By accessing the ViewHelperResolver of the RenderingContext, developers can change the ViewHelper namespace inclusions on a
global (read: per View instance) basis:

.. code-block:: php

	$resolver = $view->getRenderingContext()->getViewHelperResolver();
	// equivalent of registering namespace in template(s):
	$resolver->registerNamespace('news', 'GeorgRinger\News\ViewHelpers');
	// adding additional PHP namespaces to check when resolving ViewHelpers:
	$resolver->extendNamespace('f', 'My\Extension\ViewHelpers');
	// setting all namespaces in advance, globally, before template parsing:
	$resolver->setNamespaces(array(
		'f' => array(
			'TYPO3Fluid\\Fluid\\ViewHelpers',
			'TYPO3\\CMS\\Fluid\\ViewHelpers',
			'My\\Extension\\ViewHelpers'
		),
		'vhs' => array(
		    'FluidTYPO3\\Vhs\\ViewHelpers',
		    'My\\Extension\\ViewHelpers'
		),
		'news' => array(
			'GeorgRinger\\News\\ViewHelpers',
		);
	));

By "extending" a namespace Fluid adds additional lookup namespaces when detecting ViewHelper classes and uses the last added path first, allowing you to replace ViewHelpers by placing a class with the same sub-name in your own ViewHelpers namespace that extends Fluid's. Doing so also allows you to change the arguments the ViewHelper accepts/requires.

ViewHelpers can accept arbitrary arguments
------------------------------------------

This feature allows your ViewHelper class to receive any number of additional arguments using any names you desire. It works by
separating the arguments that are passed to each ViewHelper into two groups: those that are declared using `registerArgument`
(or render method arguments), and those that are not. Those that are not declared are then passed to a special function -
`handleAdditionalArguments` - on the ViewHelper class, which in the default implementation throws an error if additional
arguments exist. So by overriding this method in your ViewHelper you can change if and when the ViewHelper should throw an
error on receiving unregistered arguments.

This feature is also the one allowing TagBasedViewHelpers to freely accept arbitrary `data-` prefixed arguments without
failing - on TagBased ViewHelpers, the `handleAdditionalArguments` method simply adds new attributes to the tag that gets
generated and throws an error if any additional arguments which are neither registered nor prefixed with `data-` are given.

ViewHelpers automatically compilable
------------------------------------

All ViewHelpers, including those you write yourself, are now automatically compilable. This means you no longer have to care
about implementing the CompilableInterface or a custom `compile()` function, and that every Fluid template can now be cached
to a compiled PHP script regardless of ViewHelpers.

ViewHelpers still are able to define a custom `compile()` function but are no longer required to do so. When they don't define
such a method, an execution is chosen which is identical in performance to calling the ViewHelper from a template that before
this could not be compiled. The ViewHelpers that do define a custom compiling method can further increase performance.

When you explicitly require a ViewHelper of yours to prevent template caching it is possible to implement a custom `compile()`
method which calls `$templateParser->disable();` and nothing else. Doing this disables the compiling inside the scope (template,
partial or section) currently being rendered.

New and more efficient escaping
-------------------------------

Contrary to earlier versions of Fluid which used a ViewHelperNode for `f:format.htmlentities` around other nodes it wished to
escape, standalone Fluid has implemented a custom SyntaxTreeNode type which does the escaping in a more efficient manner
(directly using `htmlentities`). Although it means you cannot override this escaping behaviour by overriding the
`f:format.htmlentities` ViewHelper (which is completely possible to do with Fluid now) it should mean a significant boost to
performance as it avoids an excessive amount of ViewHelper resolving and -rendering operations, replacing them with a single PHP
function call wrapped in a tiny class, which compiles also to a single function call and which compiles in a way that it wraps
the compiled output of the Node it escapes as a pure string operation.

Escaping interception is still contained within the `Configuration` instance given to the TemplateParser - and those can be
manipulated with a custom RenderingContext (see above).

.. index:: Fluid, PHP-API, Frontend, Backend
