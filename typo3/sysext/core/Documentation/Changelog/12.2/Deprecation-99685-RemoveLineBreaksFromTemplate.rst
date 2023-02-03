.. include:: /Includes.rst.txt

.. _deprecation-99685-1674497039:

================================================================
Deprecation: #99685 - PageRenderer::removeLineBreaksFromTemplate
================================================================

See :issue:`99685`

Description
===========

The following methods have been marked as deprecated and will be removed
in TYPO3 v13:

*   :php:`\TYPO3\CMS\Core\Page\PageRenderer::enableRemoveLineBreaksFromTemplate()`
*   :php:`\TYPO3\CMS\Core\Page\PageRenderer::disableRemoveLineBreaksFromTemplate()`
*   :php:`\TYPO3\CMS\Core\Page\PageRenderer::getRemoveLineBreaksFromTemplate()`

The methods provide a means to remove line break characters from the rendered output,
what would reduce the size of the response. There are better options available nowadays
though and no need to rely on a static code replacement.

Impact
======

Using the methods will raise a deprecation level log entry and will stop
working with TYPO3 v13.


Affected installations
======================

Instances with extensions that call these methods are affected.

The extension scanner reports shows usages found.


Migration
=========

These methods only remove linebreaks from the rendered HTML output. They are not
much use in terms of reducing response size. Migrate to a proper output
optimization tool like `tidy <https://www.html-tidy.org/>`__.

All calls to the deprecated messages should be removed from the codebase.

.. index:: Backend, TCA, FullyScanned, ext:core
