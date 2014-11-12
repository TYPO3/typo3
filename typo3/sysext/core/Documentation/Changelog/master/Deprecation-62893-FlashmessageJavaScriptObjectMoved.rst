==================================================================================
Deprecation: #62893 - Flashmessage JavaScript object TYPO3.Flashmessages was moved
==================================================================================

Description
===========

Flashmessages JavaScript object was moved from ``TYPO3.Flashmessages`` to ``top.TYPO3.Flashmessages``.
The severity constant values was changed to correspond to the same values (-2,-1,0,1,2) of the constants as in PHP.
The constants ``TYPO3.Severity.information`` was marked as deprecated.
3rd party extensions referring to ``TYPO3.Severity.information`` will work until CMS 9.
A compatibility file was introduced to map ``TYPO3.Flashmessages`` to ``top.TYPO3.Flashmessages``, will also work until CMS 9.


Impact
======

If a 3rd party extension calls the mentioned methods directly, a deprecation log will be written to the browser console.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension refers to the method ``TYPO3.Flashmessages.display()`` or use ``TYPO3.Severity.information`` constants.


Migration
=========

The affected 3rd party extensions must be modified to use ``top.TYPO3.Flashmessages`` instead of ``TYPO3.Flashmessages``.

Example:

    .. code-block:: javascript

    // Old and deprecated:
    TYPO3.Flashmessages.display(TYPO3.Severity.notice)

    // New and the only correct way:
    top.TYPO3.Flashmessages.display(top.TYPO3.Severity.notice)

    ..

The ``TYPO3.Severity`` object moved to ``top.TYPO3.Severity``. Use ``top.TYPO3.Severity.*`` instead.
