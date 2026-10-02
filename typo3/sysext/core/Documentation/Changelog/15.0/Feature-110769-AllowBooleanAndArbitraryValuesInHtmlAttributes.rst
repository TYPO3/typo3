..  include:: /Includes.rst.txt

..  _feature-110769-1790196401:

========================================================================
Feature: #110769 - Allow boolean and arbitrary values in HTML attributes
========================================================================

See :issue:`110769`

Description
===========

:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::implodeAttributes()` now has an
optional fourth parameter :php:`$convertValues`. When enabled, values are
converted for HTML5 output:

*   :php:`true` renders an attribute without a value, for example
    :html:`defer` instead of :html:`defer="defer"`
*   :php:`false` and :php:`null` omit the attribute
*   objects implementing :php:`\Stringable` are cast to string
*   other arrays and objects are JSON-encoded and escaped for the attribute
*   all other values are escaped with :php:`htmlspecialchars()`

Example:

..  code-block:: php

    GeneralUtility::implodeAttributes([
        'src' => '/file.js',
        'defer' => true,
        'nomodule' => false,
        'data-config' => ['a' => 1],
    ], true, false, true);
    // src="/file.js" defer data-config="{&quot;a&quot;:1}"

The option is used when rendering script, link and style tags in
:php:`\TYPO3\CMS\Core\Page\PageRenderer`,
:php:`\TYPO3\CMS\Core\Page\AssetRenderer` and
:php:`\TYPO3\CMS\Core\Page\JavaScriptRenderer`, as well as for the
:html:`<meta>` tags of the meta tag managers, the :html:`<html>` tag
configured via :typoscript:`config.htmlTag.attributes` and the
:html:`<video>` and :html:`<audio>` tags.

Impact
======

Extension authors can pass boolean values and arrays as tag attributes, for
example via `tagAttributes` of the :php-short:`\TYPO3\CMS\Core\Page\PageRenderer` or the
`additionalAttributes` option of the video and audio renderers.

The default behavior of :php:`implodeAttributes()` is unchanged.

In the video and audio renderers, :php:`true` in `additionalAttributes`
now renders an attribute without a value (:html:`playsinline`) instead of
:html:`playsinline="1"`, and :php:`false` removes the attribute, including
default ones such as :html:`controls`.

Enumerated attributes such as :html:`aria-hidden` or :html:`draggable` must
still be passed as the strings :php:`'true'` or :php:`'false'`, since
omitting them is not the same as setting them to "false". Attributes without a
value are not XML-compliant.

..  index:: PHP-API, Frontend, ext:core
