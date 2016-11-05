.. include:: ../../Includes.txt

=========================================================
Feature: #23494 - Add stdWrap to config.additionalHeaders
=========================================================

See :issue:`23494`

Description
===========

Add stdWrap to the elements of the additionalHeaders array. This gives full control over sending an HTTP header.

.. code-block:: typoscript

	config.additionalHeaders {
	  10.header = foo:
	  10.header.dataWrap = |{page:uid}
	}


Impact
======

Allow to use stdWrap on the elements `header`, `replace` and `httpResponseCode`. Empty headers will be skipped now.

.. index:: TypoScript
