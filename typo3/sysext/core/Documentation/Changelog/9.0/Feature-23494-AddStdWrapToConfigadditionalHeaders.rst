.. include:: ../../Includes.txt

=========================================================
Feature: #23494 - Add stdWrap to config.additionalHeaders
=========================================================

See :issue:`23494`

Description
===========

Add :ts:`stdWrap` to the elements of the :ts:`additionalHeaders` array. This gives full control over sending an HTTP header.

.. code-block:: typoscript

	config.additionalHeaders {
	  10.header = foo:
	  10.header.dataWrap = |{page:uid}
	}


Impact
======

Allow to use stdWrap on the elements :ts:`header`, :ts:`replace` and :ts:`httpResponseCode`. Empty headers will be skipped now.

.. index:: TypoScript, Frontend
