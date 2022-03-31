
.. include:: /Includes.rst.txt

========================================================
Feature: #66445 - Add file extension to mimeType mapping
========================================================

See :issue:`66445`

Description
===========

As a fix for wrong mimeType detection for SVG files without XML prologue we added a new setting to map known file extensions to mimeTypes.
The new setting is `$GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']` which contains an array:

.. code-block:: php

	array(
		'svg' => 'image/svg+xml'
	)


Impact
======

The automatic detection for mimeTypes works great, but in some special cases not.
This new setting should only be used, if the automatic detection fails.


.. index:: LocalConfiguration
