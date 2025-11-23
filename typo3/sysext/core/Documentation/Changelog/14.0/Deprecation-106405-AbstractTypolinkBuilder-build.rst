..  include:: /Includes.rst.txt

..  _deprecation-106405-1742674605:

=====================================================
Deprecation: #106405 - AbstractTypolinkBuilder->build
=====================================================

See :issue:`106405`

Description
===========

The :php:`build()` method in
:php:`\TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder`
has been deprecated in favor of the new
:php:`\TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface`.

When creating custom TypolinkBuilder classes, the traditional approach was to:

1.  Extend :php:`\TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder`
2.  Implement the :php:`build()` method
3.  Receive dependencies via the constructor

This approach is now deprecated.
The new, recommended pattern is to implement
:php-short:`\TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface`
directly.

The :php:`ContentObjectRenderer` property in
:php-short:`\TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder`
has also been deprecated and will be removed in TYPO3 v15.0.
It should now be accessed via the :php:`\Psr\Http\Message\ServerRequestInterface`
object instead.

Impact
======

Extension authors who have created custom TypolinkBuilder classes that extend
:php-short:`\TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder` will see
deprecation warnings when their link builders are used.

Deprecation warnings occur when:

*   A custom TypolinkBuilder class still implements the :php:`build()` method
*   Code accesses the deprecated :php:`$contentObjectRenderer` property
*   Dependencies are passed through the constructor instead of DI

Affected installations
======================

TYPO3 installations with extensions that:

*   Create custom TypolinkBuilder classes extending
    :php-short:`\TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder`
*   Override or extend the :php:`build()` method
*   Access the :php:`$contentObjectRenderer` property directly

Migration
=========

For end users, link generation continues to work as before using either:

*   :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer`,
    method `typolink()`
*   :php-short:`\TYPO3\CMS\Frontend\Typolink\LinkFactory`

These public APIs are not affected and do not trigger deprecations.

For extension developers, custom TypolinkBuilder implementations should now
use the new :php:`\TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface`.

1.  Implement the new interface:

..  code-block:: php
    :caption: Recommended migration approach

    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
    use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
    use TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface;

    // Before (deprecated)
    class MyCustomLinkBuilder extends AbstractTypolinkBuilder
    {
        public function build(
            array &$linkDetails,
            string $linkText,
            string $target,
            array $conf
        ): LinkResultInterface {
            // Custom logic using $this->contentObjectRenderer
        }
    }

    // After (recommended)
    class MyCustomLinkBuilder implements TypolinkBuilderInterface
    {
        public function buildLink(
            array $linkDetails,
            array $configuration,
            ServerRequestInterface $request,
            string $linkText = ''
        ): LinkResultInterface {
            $contentObjectRenderer = $request->getAttribute(
                'currentContentObject'
            );
            // Custom logic using $contentObjectRenderer
        }
    }

2.  Use dependency injection for required services instead of accessing them
    through global state or constructor arguments.

3.  Retrieve :php:`ContentObjectRenderer` from the request object rather than
    the deprecated property.

All implementations of
:php-short:`\TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface`
are automatically registered as **public services** in the Dependency Injection
container, so no manual service configuration is necessary.

Extensions can maintain backward compatibility during the transition by
implementing both the old :php:`build()` and the new :php:`buildLink()` methods.

..  index:: PHP-API, NotScanned, ext:frontend
