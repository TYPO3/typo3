.. include:: /Includes.rst.txt

.. _breaking-102614-1701869646:

================================================================
Breaking: #102614 - Removed Hook for manipulating GetData result
================================================================

See :issue:`102614`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getData']`
has been removed in favor of the new PSR-14 event
:php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterGetDataResolvedEvent`.


Impact
======

Any hook implementation registered is not executed anymore
in TYPO3 v13.0+.


Affected installations
======================

TYPO3 installations with custom extensions using this hook.


Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v12 (using the hook) and v13+ (using the new event)
when implementing the event as well without any further deprecations.
Use the :doc:`PSR-14 event <../13.0/Feature-102614-PSR-14EventForModifyingGetDataResult>`
to allow greater influence in the functionality.

.. note::

    The new event is no longer executed for every "section" of the provided
    parameter string, but only once, before the final result of :php:`getData()`
    is about to be returned. This therefore means, the former :php:`$secVal`
    is no longer available in the new event. Please adjust your implementation
    accordingly.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
