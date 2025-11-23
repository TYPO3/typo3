..  include:: /Includes.rst.txt

..  _feature-106405-1742674556:

===========================================
Feature: #106405 - TypolinkBuilderInterface
===========================================

See :issue:`106405`

Description
===========

A new interface :php:`\TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface`
has been introduced to provide a more flexible way to generate links in TYPO3.

The interface defines a :php:`buildLink()` method that replaces the previous
:php:`build()` method approach used when extending from
:php-short:`\TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder`.

All Core `TypolinkBuilder` implementations now implement this interface and
use dependency injection for improved service composition and testability.

The interface method signature is as follows:

..  code-block:: php

    use TYPO3\CMS\Frontend\Typolink;

    public function buildLink(
        array $linkDetails,
        array $configuration,
        ServerRequestInterface $request,
        string $linkText = ''
    ): LinkResultInterface;

Impact
======

*   All implementations of
    :php-short:`\TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface` are
    automatically configured as public services in the dependency injection
    container, removing the need for manual service configuration.

*   `TypolinkBuilder` classes can now use proper dependency injection through
    their constructors, improving testability and aligning with TYPO3's
    architectural best practices.

*   The :php-short:`\Psr\Http\Message\ServerRequestInterface` is now passed
    directly, providing access to the request context without relying on global
    state.

*   The new interface introduces a cleaner separation of concerns
    and more explicit parameter passing.

Example usage
=============

Creating a custom `TypolinkBuilder` using the new interface:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Typolink/MyCustomLinkBuilder.php

    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Frontend\Typolink\LinkResult;
    use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
    use TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface;

    final readonly class MyCustomLinkBuilder implements TypolinkBuilderInterface
    {
        public function __construct(
            private MyCustomService $customService,
            private AnotherService $anotherService,
        ) {}

        public function buildLink(
            array $linkDetails,
            array $configuration,
            ServerRequestInterface $request,
            string $linkText = ''
        ): LinkResultInterface {
            // Access ContentObjectRenderer from the request
            $contentObjectRenderer = $request->getAttribute('currentContentObject');

            // Use injected services
            $processedData = $this->customService->process($linkDetails);

            // Build and return link result
            return new LinkResult($processedData['url'], $linkText);
        }
    }

Registering the TypolinkBuilder class is still necessary via
:php:`$GLOBALS['TYPO3_CONF_VARS']`.

..  index:: PHP-API, ext:frontend
