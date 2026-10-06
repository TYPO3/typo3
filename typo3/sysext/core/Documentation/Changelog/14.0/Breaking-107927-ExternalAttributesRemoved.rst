.. include:: /Includes.rst.txt

.. _breaking-107927-1763052738:

=========================================================================================
Breaking: #107927 - Remove "external" property / option from TypoScript and AssetRenderer
=========================================================================================

See :issue:`107927`

Description
===========

The :typoscript:`resource` property :typoscript:`external` in the :typoscript:`PAGE` properties
:typoscript:`includeCSS`, :typoscript:`includeCSSLibs`, :typoscript:`includeJS`,
:typoscript:`includeJSFooter`, :typoscript:`includeJSFooterlibs` and :typoscript:`includeJSLibs`
is now obsolete.

This also obsoletes the :ref:`External option in AssetRenderer <feature-102255-1726090749>`.

Both are removed in favor of the new unified :ref:`URI resource definition <feature-107537-1759136314>`.

Instead of marking URIs as URIs with an additional option, prefix the URI, that shall be used with `URI:`,
or simply use absolute URLs starting with `http(s)://` or `//`, where the prefix is not required.

This URI resource definition will work across the system, not only in TypoScript or `AssetCollector`/ `AssetRenderer`.

Impact
======

Using the `external` property in TypoScript or the `external` option in `AssetCollector`
will have no effect any more. If absolute URLs have been used as resources, everything
will work as before. Relative URIs **must** be prefixed with `URI:` from now on. A relative
path without the prefix is only resolved if a file with this path exists in the public directory.

Additionally, the string after the `URI:` keyword **must** be a valid URI.

A resource that cannot be resolved, for example a relative URI without the prefix or an
invalid URI after the prefix, has the following effect:

*   The :typoscript:`PAGE` properties :typoscript:`includeCSS`, :typoscript:`includeCSSLibs`,
    :typoscript:`includeJS`, :typoscript:`includeJSFooter`, :typoscript:`includeJSFooterlibs` and
    :typoscript:`includeJSLibs` leave out the file. No exception is thrown.
*   `AssetCollector` and other PHP code that resolves the resource directly throw an exception.

Before this change, invalid URIs (marked as external) would have been rendered
to HTML, without any obvious feedback for developers or integrators. Browsers then ignored
such invalid references.

Affected installations
======================

TYPO3 installations using the `external` property in TypoScript or the `external` option in `AssetCollector`.

Migration
=========

TypoScript before:

..  code-block:: typoscript

    page = PAGE
    page.includeCSS {
        main = https://example.com/styles/main.css
        main.external = 1
        other = /styles/main.css
        other.external = 1
    }

TypoScript after:

..  code-block:: typoscript

    page = PAGE
    page.includeCSS {
        main = https://example.com/styles/main.css
        other = URI:/styles/main.css
    }

PHP Code before:

..  code-block:: php

    $assetCollector->addStyleSheet(
        'myCssFile',
        '/styles/main.css',
        [],
        ['external' => true]
    );

PHP Code after:

..  code-block:: php

    $assetCollector->addStyleSheet(
        'myCssFile',
        'URI:/styles/main.css',
    );

.. index:: Frontend, NotScanned, ext:frontend
