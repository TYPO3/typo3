.. include:: /Includes.rst.txt

.. _breaking-97065:

==========================================================
Breaking: #97065 - TYPO3 Frontend always rendered in UTF-8
==========================================================

See :issue:`97065`

Description
===========

For historical reasons, it was possible to change the actual rendering charset
of TYPO3's Frontend Output to a specific character set, and also to modify the
"renderCharset", which was removed in TYPO3 v8.0. Since TYPO3 v6, the default
rendering output was set to "utf-8", and nowadays, it has become a niche
to change the output rendering charset to a different value than UTF-8.

For this reason, the TypoScript setting :typoscript:`config.metaCharset` has no effect
anymore as all rendering for Frontend is "utf-8" and not changeable anymore.

If this TypoScript setting was set to "utf-8" in previous installations,
this line could have been removed anyways already.

The public PHP property :php:`TypoScriptFrontendController->metaCharset` is
removed, along with the public method
:php:`TypoScriptFrontendController->convOutputCharset()`.

Impact
======

TYPO3 installations with a different setting than "utf-8" will now output
"utf-8" output at all times.

TYPO3 extensions accessing the removed property will trigger a PHP warning, or
calling the removed method :php:`convOutputCharset()` will see a fatal PHP error.

Affected Installations
======================

TYPO3 installations using :typoscript:`config.metaCharset` set to a value other than
`utf-8`, or accessing the removed property or method. The Extension Scanner
in the Install Tool will detect usages of the removed property and method.

Migration
=========

TYPO3 Installations with a different charset than UTF-8 should convert their own
content in a custom middleware, as this specific use-case is not supported by
TYPO3 Core anymore.

TYPO3 installations with TypoScript option set :typoscript:`config.metaCharset = utf-8` can
remove the TypoScript line in previous supported TYPO3 versions.

Any usage of the removed property / method should be removed.

.. index:: Frontend, PHP-API, TypoScript, FullyScanned, ext:frontend
