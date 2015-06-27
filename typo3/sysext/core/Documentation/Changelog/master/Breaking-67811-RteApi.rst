==========================
Breaking: #67811 - Rte API
==========================

Description
===========

The ``RTE`` implementation was based on the main classes ``\TYPO3\CMS\Backend\Rte\AbstractRte``,
``\TYPO3\CMS\Rtehtmlarea\RteHtmlAreaBase`` and ``\TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi``. These
three main API classes contain changed signatures and internal method calls.


Impact
======

Extensions that extend those classes and rely on methods being called are likely to break.


Affected Installations
======================

Extensions that extend one of the above mentioned extensions.


Migration
=========

No details yet.