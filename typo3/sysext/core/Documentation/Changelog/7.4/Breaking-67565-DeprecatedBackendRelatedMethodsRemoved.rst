
.. include:: /Includes.rst.txt

=============================================================
Breaking: #67565 - Deprecated backend related methods removed
=============================================================

See :issue:`67565`

Description
===========

The following deprecated methods have been removed:


.. code-block:: php

	DocumentTemplate::formWidthText()
	PageLayoutView::getBackendLayoutConfiguration()
	PageLayoutView::wordWrapper()


Impact
======

Calls to these methods will result in a fatal error.


Affected Installations
======================

Instances with third-party extensions calling one of these methods.


.. index:: PHP-API, Backend
