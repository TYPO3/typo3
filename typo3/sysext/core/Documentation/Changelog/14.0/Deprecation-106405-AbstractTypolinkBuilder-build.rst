..  include:: /Includes.rst.txt

..  _deprecation-106405-1742674605:

=====================================================
Deprecation: #106405 - AbstractTypolinkBuilder->build
=====================================================

See :issue:`106405`

Description
===========

The :php:`build()` method in :php:`TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder`
has been deprecated in favor of the new :php:`TypolinkBuilderInterface`.

When creating custom TypolinkBuilder classes, the traditional approach of:

1. Extending :php:`AbstractTypolinkBuilder`
2. Implementing the :php:`build()` method
3. Receiving dependencies via constructor

This approach is now deprecated. The new recommended approach is to
implement the :php:`TypolinkBuilderInterface`.

The :php:`ContentObjectRenderer` property in :php:`AbstractTypolinkBuilder`
is also deprecated and will be removed in TYPO3 v15.0. The
:php:`ContentObjectRenderer` should be accessed via the
:php:`ServerRequestInterface` object instead.


Impact
======

Extension authors who have created custom TypolinkBuilder classes
extending from :php:`AbstractTypolinkBuilder` will see deprecation
warnings when their link builders are used.

The deprecation warnings will be triggered when:

* Custom TypolinkBuilder classes still use the :php:`build()` method
* Code relies on the :php:`$contentObjectRenderer` property
* The old constructor approach is used for dependency handling


Affected installations
======================

TYPO3 installations with extensions that:

* Create custom TypolinkBuilder classes extending :php:`AbstractTypolinkBuilder`
* Override or extend the :php:`build()` method
* Access the :php:`$contentObjectRenderer` property directly


Migration
=========

For end users, generating links works the same way as before via:

* :php:`ContentObjectRenderer->typolink()` method
* :php:`LinkFactory` class

No deprecation warnings will be triggered when using the public APIs.

For extension developers with custom TypolinkBuilder classes:

1. Implement the new interface:

..  code-block:: php
    :caption: Recommended migration approach

    // Before (deprecated)
    class MyCustomLinkBuilder extends AbstractTypolinkBuilder
    {
        public function build(array &$linkDetails, string $linkText, string $target, array $conf): LinkResultInterface
        {
            // Custom logic using $this->contentObjectRenderer
        }
    }

    // After (recommended)
    class MyCustomLinkBuilder implements TypolinkBuilderInterface
    {
        public function buildLink(array $linkDetails, array $configuration, ServerRequestInterface $request, string $linkText = ''): LinkResultInterface
        {
            $contentObjectRenderer = $request->getAttribute('currentContentObject');
            // Custom logic using $contentObjectRenderer
        }
    }

2. Use dependency injection for required services instead of accessing
   them via global state or constructor arguments.

3. Access ContentObjectRenderer via the request object rather than
   the deprecated property.

Note: All implementations of :php:`TypolinkBuilderInterface` are automatically
configured as public services in the DI container - no manual service
configuration is needed.

Extensions can maintain backward compatibility during the transition period
by implementing both the old :php:`build()` method and the new
:php:`buildLink()` method.

..  index:: PHP-API, NotScanned, ext:frontend
