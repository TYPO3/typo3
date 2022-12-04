.. include:: /Includes.rst.txt

.. _deprecation-99170-1669411707:

=================================================================
Deprecation: #99170 - config.baseURL and <base> tag functionality
=================================================================

See :issue:`99170`

Description
===========

The TypoScript option :typoscript:`config.baseURL` has been deprecated.

The option allowed to set a fixed URL which was then added as :html:`<base>` tag
to the HTML :html:`<head>` part of a website. This feature was particularly
useful back in previous TYPO3 versions in combination with RealURL for
providing absolute links.

However, TYPO3 v9 introduced site handling, which produces absolute
URLs or absolute paths directly. In addition, with TYPO3 v12.1 the option
:ref:`config.forceAbsoluteUrls = 1 <feature-87919-1667984808>` allows to
generate absolute URLs completely for all links, images or assets, making the
baseURL option obsolete, as it isn't as powerful as the mentioned alternatives:
It only allows to define a static value rather than loading the information
based on the current request. With the TypoScript setting this is only possible
with having multiple variants of :typoscript:`config.baseURL` set via TypoScript
conditions.

In addition to the TypoScript option, the related public PHP methods are now
obsolete and have also been deprecated:

* :php:`\TYPO3\CMS\Core\Page\PageRenderer->setBaseUrl()`
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->getBaseUrl()`
* :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->baseUrlWrap()`


Impact
======

Setting the TypoScript option :typoscript:`config.baseURL` will trigger a
deprecation message, but will continue to work in TYPO3 v12.

Calling any of the PHP methods directly in PHP code will also trigger a
deprecation message.


Affected installations
======================

TYPO3 installations using the :typoscript:`config.baseURL` option, which is
common for projects which were started before TYPO3 v9.


Migration
=========

Use the site configuration with fully-qualified domain names to achieve the same
result, as rendering a :html:`<base>` tag in HTML will not be supported
out-of-the-box anymore by TYPO3 v13.

If you are already using the site configuration, but need to build
fully-qualified URLs, you can safely remove the TypoScript option
:typoscript:`config.baseURL` without any impact in 99% of the use cases.

In special cases the option :typoscript:`config.forceAbsoluteUrls = 1` can
help you to achieve the same result.

If you need to manually set a :html:`<base>` tag, this is still possible via
TypoScript:

..  code-block:: typoscript

    page = PAGE
    page.headTag.append = TEXT
    page.headTag.append.value = <base href="https://static.example.com/">

In general, it is recommended not to use the :html:`<base>` tag, as
certain crawlers cannot interpret this HTML tag properly.

.. index:: TypoScript, FullyScanned, ext:frontend
