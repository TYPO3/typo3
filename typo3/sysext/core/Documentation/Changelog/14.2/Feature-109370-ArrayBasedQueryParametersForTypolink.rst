..  include:: /Includes.rst.txt

..  _feature-109370-1742911200:

=============================================================
Feature: #109370 - Array-based queryParameters for page links
=============================================================

See :issue:`109370`

Description
===========

When page links are created programmatically by the
:php-short:`\TYPO3\CMS\Frontend\Typolink\LinkFactory` PHP API,
query parameters previously had to be provided as a URL-encoded string in
the `additionalParams` configuration key. A new `queryParameters`
configuration key has been introduced that accepts a PHP array, including
multi-dimensional arrays.

When both `queryParameters` and `additionalParams` are set, they are
merged using :php:`array_replace_recursive()`, with `queryParameters`
taking precedence.

Example:

..  code-block:: php

    $linkFactory->create('Link text', [
        'parameter' => 42,
        'queryParameters' => [
            'tx_news' => [
                'action' => 'show',
                'id' => 123,
            ],
        ],
    ], $contentObjectRenderer);

The Fluid ViewHelpers `<f:link.page>`, `<f:uri.page>`,
`<f:link.action>`, and `<f:uri.action>` now use this option
internally to pass their `additionalParams` argument as an
array, eliminating the previous serialize/deserialize round trip via query
string encoding.

Impact
======

Developers creating page links via the
:php-short:`\TYPO3\CMS\Frontend\Typolink\LinkFactory` PHP API can now pass
query parameters as structured arrays via the `queryParameters`
configuration key. This avoids manual query string encoding and makes
multi-dimensional parameter handling more natural.

The option can be combined with the existing string-based
`additionalParams`. When both are provided, `queryParameters` values
override matching keys from `additionalParams`.

..  index:: Frontend, PHP-API, ext:frontend
