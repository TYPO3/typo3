.. include:: ../../Includes.txt

================================================
Deprecation: #95318 - TypoScript parseFunc.sword
================================================

See :issue:`95318`

Description
===========

The TypoScript option `parseFunc.sword` allows to wrap
search words (such as defined via GET parameter `sword_list%5B%5D=MySearchText`)
in a special wrap when `no_cache=1` is set. This functionality has been
deprecated as this feature only works in non_cached environments, which
is not a recommended solution by TYPO3.

Since this behavior is enabled by default, it is highly recommended to avoid
this in general, which can be achieved by disabling the `no_cache=1` GET parameter
in DefaultConfiguration.php.

Also, such an option within `parseFunc` does not cover all cases to highlight
a search word, such as in headlines or HTML content which is not rendered
via `parseFunc`.


Impact
======

Websites called via `https://example.com/?no_cache=1&sword_list%5B%5D=MySearchText` and a custom sword
wrap will trigger a deprecation notice.

As this feature is seldom used and only configured with indexed
search as desired functionality, deprecations are only triggered
when explicitly configured.

In addition, this feature only works if `disableNoCacheParameter`
is disabled or `config.no_cache = 1` is explicitly set via TypoScript
which is also not recommended in production.


Affected Installations
======================

TYPO3 installations actively using the GET argument `sword_list` and have
no_cache` as allowed GET argument enabled, usually in cases where indexed
search is in use.


Migration
=========

It is recommended to implement this functionality on the client-side via
JavaScript as a custom solution, when this feature is needed.

Setting `lib.parseFunc.sword = ` will actively disable the functionality
and not trigger any deprecation warning as well.

Setting `lib.parseFunc.sword = <span class="ce-sword">|</span>`
will also trigger no deprecation warning for TYPO3 v11.

.. index:: Frontend, TypoScript, NotScanned, ext:frontend
