..  include:: /Includes.rst.txt

..  _feature-109187-1774695398:

======================================================================
Feature: #109187 - Automatic SRI hash resolution for resource includes
======================================================================

See :issue:`109187`

Description
===========

Setting :typoscript:`integrity = auto` on any resource include that supports
the :typoscript:`integrity` property causes TYPO3 to automatically compute and
inject the Subresource Integrity (SRI) hash for that resource instead of
requiring a manually pre-computed hash value.

This works for all integrity-supporting TypoScript include properties:

*  :typoscript:`page.includeCSS`
*  :typoscript:`page.includeCSSLibs`
*  :typoscript:`page.includeJS`
*  :typoscript:`page.includeJSLibs`
*  :typoscript:`page.includeJSFooter`
*  :typoscript:`page.includeJSFooterlibs`

The hash is computed using SHA-256 and the result is cached via
:php:`cache.assets` with a 7-day TTL, so there is no per-request overhead
after the first render for a given resource.

For external URL resources, :typoscript:`crossorigin="anonymous"` is added
automatically when the hash is successfully resolved, as required by the SRI
specification for cross-origin resources.

..  note::
    When using :typoscript:`integrity = auto` for remote HTTP(S) URLs, TYPO3
    fetches the resource from the remote server to compute its hash. The
    computed hash reflects the content returned by the remote server at the
    time of the first fetch. **The remote server must therefore be trusted.**
    If the remote resource is compromised or altered at the time of the initial
    fetch, the hash will be computed from the compromised content and browsers
    will subsequently accept it. For maximum security, use explicit
    pre-computed hash values (e.g. :typoscript:`integrity = sha256-abc123==`)
    obtained from a trusted source rather than relying on automatic resolution
    for externally hosted resources.

The equivalent PHP constant :php:`\TYPO3\CMS\Core\Page\ResourceHashCollection::AUTO`
can be used when calling the :php:`PageRenderer` or :php:`AssetCollector` APIs
directly.

Impact
======

It is now possible to enable SRI for resource includes without manually
computing the hash value. Setting :typoscript:`integrity = auto` is sufficient:

..  code-block:: typoscript

    page.includeCSS {
        main = https://cdn.example.com/styles/main.css
        main.integrity = auto
        # crossorigin="anonymous" is added automatically for external URLs
    }

    page.includeJS {
        app = EXT:my_extension/Resources/Public/JavaScript/app.js
        app.integrity = auto
    }

This results in output such as:

..  code-block:: html

    <link rel="stylesheet" href="https://cdn.example.com/styles/main.css" media="all" integrity="sha256-abc123==" crossorigin="anonymous">
    <script src="/typo3conf/ext/my_extension/Resources/Public/JavaScript/app.js" integrity="sha256-xyz789=="></script>

When using the PHP API directly, pass :php:`ResourceHashCollection::AUTO` as
the :php:`$integrity` argument:

..  code-block:: php
    :emphasize-lines: 21, 33, 40

    use TYPO3\CMS\Core\Page\AssetCollector;
    use TYPO3\CMS\Core\Page\PageRenderer;
    use TYPO3\CMS\Core\Page\ResourceHashCollection;

    // Ignore all parameters but the last one ($integrity). They are defaults,
    // but must be specified because TYPO3 Core API does not provide stable
    // argument names, so using named arguments for regular methods is not supported.

    $pageRenderer->addCssFile(
        'EXT:my_extension/Resources/Public/Css/style.css',
        'stylesheet',
        'all',
        '',
        null,
        false,
        '',
        null,
        '|',
        false,
        [],
        ResourceHashCollection::AUTO, // parameter $integrity
    );

    $pageRenderer->addJsFile(
        'EXT:my_extension/Resources/Public/JavaScript/app.js',
        '',
        null,
        false,
        '',
        null,
        '|',
        false,
        ResourceHashCollection::AUTO, // parameter $integrity
    );

    $assetCollector->addStyleSheet(
        'my-styles',
        'EXT:my_extension/Resources/Public/Css/style.css',
        [],
        ['integrity' => ResourceHashCollection::AUTO], // parameter $options
    );

.. index:: Frontend, PHP-API, TypoScript, ext:frontend, ext:core
