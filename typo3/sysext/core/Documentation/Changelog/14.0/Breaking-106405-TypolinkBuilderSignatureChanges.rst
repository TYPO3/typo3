..  include:: /Includes.rst.txt

..  _breaking-106405-1742674605:

=====================================================
Breaking: #106405 - TypolinkBuilder signature changes
=====================================================

See :issue:`106405`

Description
===========

To enable dependency injection for TypolinkBuilder classes, several breaking
changes were introduced to the TypolinkBuilder architecture.

The following breaking changes have been made:

* The constructor of :php:`TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder`
  has been removed. Extending classes can no longer rely on receiving
  :php:`ContentObjectRenderer` and :php:`TypoScriptFrontendController`
  through the constructor.

* All concrete TypolinkBuilder implementations now implement the new
  :php:`TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface` and use
  dependency injection via their constructors instead of extending
  :php:`AbstractTypolinkBuilder` with constructor arguments.

* The method signature of the main link building method has changed from
  :php:`build(array &$linkDetails, string $linkText, string $target, array $conf)`
  to :php:`buildLink(array $linkDetails, array $configuration, ServerRequestInterface $request, string $linkText = '')`.


Impact
======

Custom TypolinkBuilder implementations extending
:php:`AbstractTypolinkBuilder` will fail with fatal errors due to the
removed constructor and changed method signatures.

Extensions that instantiate TypolinkBuilder classes directly will also
fail, as the constructor signatures have fundamentally changed to use
dependency injection.


Affected installations
======================

TYPO3 installations with extensions that:

* Create custom TypolinkBuilder classes extending :php:`AbstractTypolinkBuilder`
* Directly instantiate TypolinkBuilder classes in PHP code
* Override or extend the :php:`build()` method of TypolinkBuilder classes


Migration
=========

For custom TypolinkBuilder implementations:

1. Implement :php:`TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface`
2. Use dependency injection in the constructor for required services
3. Replace the :php:`build()` method with :php:`buildLink()`

Note: Classes implementing :php:`TypolinkBuilderInterface` are automatically
configured as public services in the DI container - no manual configuration
is required.

Example migration:

..  code-block:: php
    :caption: Before (TYPO3 v13 and lower)

    use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;

    class MyCustomLinkBuilder extends AbstractTypolinkBuilder
    {
        public function build(array &$linkDetails, string $linkText, string $target, array $conf): LinkResultInterface
        {
            // Custom link building logic
            return new LinkResult('news', $linkText);
        }
    }

..  code-block:: php
    :caption: After (TYPO3 v14+)

    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface;

    class MyCustomLinkBuilder implements TypolinkBuilderInterface
    {
        public function __construct(
            // Inject required dependencies
        ) {}

        public function buildLink(array $linkDetails, array $configuration, ServerRequestInterface $request, string $linkText = ''): LinkResultInterface
        {
            // Custom link building logic - access ContentObjectRenderer via:
            $contentObjectRenderer = $request->getAttribute('currentContentObject');
            return new LinkResult('news', $linkText);
        }
    }

For code that instantiates TypolinkBuilder classes directly:

It is strongly recommended to use the :php:`TYPO3\CMS\Frontend\Typolink\LinkFactory`
instead of instantiating TypolinkBuilder classes directly. The LinkFactory
handles the proper instantiation and dependency injection automatically.

..  index:: PHP-API, NotScanned, ext:frontend
