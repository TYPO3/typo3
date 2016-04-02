=================================================================
Breaking: #73445 - Remove flashmessage_compatibility.js from core
=================================================================

Description
===========

The ``flashmessage_compatibility.js`` has been removed from the core.


Impact
======

Extensions which make use of :js:`TYPO3.Flashmessage` JavaScript function will not work anymore.


Migration
=========

Use :js:`top.TYPO3.Notification.*` to create flash messages within JavaScript for the TYPO3 backend.

.. index:: javascript
