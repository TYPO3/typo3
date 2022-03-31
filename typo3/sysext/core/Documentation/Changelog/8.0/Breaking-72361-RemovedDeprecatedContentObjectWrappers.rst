
.. include:: /Includes.rst.txt

=============================================================
Breaking: #72361 - Removed deprecated content object wrappers
=============================================================

See :issue:`72361`

Description
===========

The following wrapper methods for ContentObject rendering within ContentObjectRenderer
have been removed:

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

Using the methods above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use custom calls to ContentObjects via the methods above.


Migration
=========

Replace the direct method calls to `$contentObject->COBJECT()` with the common method.

Example for the SVG() ContentObject call:

.. code-block:: php

	$cObj->cObjGetSingle('SVG', $conf);

.. index:: PHP-API, Frontend
