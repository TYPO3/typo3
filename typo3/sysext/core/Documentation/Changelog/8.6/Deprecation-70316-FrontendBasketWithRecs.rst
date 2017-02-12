.. include:: ../../Includes.txt

===============================================
Deprecation: #70316 - Frontend basket with recs
===============================================

See :issue:`70316`

Description
===========

The TypoScriptFrontendController has a basic mechanism to automatically register session data if the GET/POST
variable :code:`recs` is given. This has been deprecated. This additionally obsoletes the configuration
variable :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['maxSessionDataSize']` which has been deprecated, too.


Impact
======

Handling baskets or other session data in :code:`recs` throws a deprecation warning.


Affected Installations
======================

Extensions with a legacy that rely on this automatic basket (`tt_products` for example) should be adapted. Searching extensions
for string :php:`recs` should reveal affected parts.


Migration
=========

Use the session functions :php:`setKey()` and :php:`getKey()` of :php:`$GLOBALS['TSFE']->fe_user` directly to store session data
like basket information from within the extension.

.. index:: Frontend, PHP-API
