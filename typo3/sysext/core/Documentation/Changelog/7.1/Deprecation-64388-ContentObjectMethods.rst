
.. include:: /Includes.rst.txt

===============================================================================
Deprecation: #64388 - Direct ContentObject methods within ContentObjectRenderer
===============================================================================

See :issue:`64388`

Description
===========

The following wrapper methods for ContentObject rendering within ContentObjectRenderer
have been marked for removal for TYPO3 CMS 8.

.. code-block:: php

	FLOWPLAYER()
	TEXT()
	CLEARGIF()
	COBJ_ARRAY()
	USER()
	FILE()
	FILES()
	IMAGE()
	IMG_RESOURCE()
	IMGTEXT()
	CONTENT()
	RECORDS()
	HMENU()
	CTABLE()
	OTABLE()
	COLUMNS()
	HRULER()
	CASEFUNC()
	LOAD_REGISTER()
	FORM()
	SEARCHRESULT()
	TEMPLATE()
	FLUIDTEMPLATE()
	MULTIMEDIA()
	MEDIA()
	SWFOBJECT()
	QTOBJECT()
	SVG()


Impact
======

Using the methods above directly in any third party extension will trigger a deprecation log message.


Affected installations
======================

Instances which use custom calls to ContentObjects via the methods above.


Migration
=========

Replace the direct method calls to `$contentObject->COBJECT()` with the common method.

Example for the SVG() ContentObject call:

.. code-block:: php

	$cObj->cObjGetSingle('SVG', $conf);


.. index:: PHP-API, Frontend
