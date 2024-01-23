.. include:: /Includes.rst.txt

.. _breaking-102583-1701510037:

=====================================================
Breaking: #102583 - Removed context aspect typoscript
=====================================================

See :issue:`102583`

Description
===========

The :php:`\TYPO3\CMS\Core\Context\Context` aspect :php:`typoscript` has been
removed without direct substitution. This aspect was implemented by now removed
class :php:`\TYPO3\CMS\Core\Context\TypoScriptAspect`, handling the
EXT:adminpanel-related property :php:`forcedTemplateParsing`.


Impact
======

The following calls will throw PHP exceptions:

.. code-block:: php

    /** @var \TYPO3\CMS\Core\Context\Context $context */
    $context->getPropertyFromAspect('typoscript', 'forcedTemplateParsing');
    $context->getAspect('typoscript');
    // Returns false
    $context->hasAspect('typoscript');

Affected installations
======================

Extensions typically do not use this context aspect since it only carried an
EXT:adminpanel-related information.


Migration
=========

No direct migration possible. There should be little reason for extensions to
work with this EXT:adminpanel related detail.


.. index:: Frontend, PHP-API, PartiallyScanned, ext:core
