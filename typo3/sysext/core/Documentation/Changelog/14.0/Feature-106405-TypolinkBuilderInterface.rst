..  include:: /Includes.rst.txt

..  _feature-106405-1742674556:

===========================================
Feature: #106405 - TypolinkBuilderInterface
===========================================

See :issue:`106405`

Description
===========

A new interface :php:`TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface`
has been introduced to provide a more flexible way to generate links in TYPO3.

The interface defines a :php:`buildLink()` method that replaces the previous
:php:`build()` method approach when extending from
:php:`TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder`.

All core TypolinkBuilder implementations now implement this interface and use
dependency injection for better service composition and testability.

The interface method signature is:

..  code-block:: php

    public function buildLink(
        array $linkDetails,
        array $configuration,
        ServerRequestInterface $request,
        string $linkText = ''
    ): LinkResultInterface;

Impact
======

* All implementations of :php:`TypolinkBuilderInterface` are automatically
  configured as public services in the DI container, eliminating the need for
  manual service configuration.

* TypolinkBuilder classes can now use proper dependency injection through
  their constructors, making them more testable and following TYPO3's
  architectural patterns.

* The :php:`ServerRequestInterface` is now passed directly,
  providing better access to request context without relying on global state.

* The new interface provides a cleaner separation of concerns
  and more explicit parameter passing.


Example usage
=============

Creating a custom TypolinkBuilder with the new interface:

..  code-block:: php
    :caption: Custom TypolinkBuilder implementation

    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Frontend\Typolink\TypolinkBuilderInterface;

    class MyCustomLinkBuilder implements TypolinkBuilderInterface
    {
        public function __construct(
            private readonly MyCustomService $customService,
            private readonly AnotherService $anotherService,
        ) {}

        public function buildLink(
            array $linkDetails,
            array $configuration,
            ServerRequestInterface $request,
            string $linkText = ''
        ): LinkResultInterface {
            // Access ContentObjectRenderer from request
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
