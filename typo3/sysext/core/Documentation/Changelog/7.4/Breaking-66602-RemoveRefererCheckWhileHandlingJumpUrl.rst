
.. include:: /Includes.rst.txt

=========================================================
Breaking: #66602 - Check jumpUrl referer has been removed
=========================================================

See :issue:`66602`

Description
===========

The following method has been removed:


.. code-block:: php

	TypoScriptFrontendController::checkJumpUrlReferer()


Impact
======

Calls to this method will result in a fatal error.


Affected Installations
======================

Instances with third-party extensions calling this method.


.. index:: PHP-API, Frontend, ext:jumpurl
