
.. include:: /Includes.rst.txt

====================================================
Breaking: #77186 - ExtDirect eID entry point removed
====================================================

See :issue:`77186`

Description
===========

The frontend eID script to call the `ExtDirect` API for backend calls has been removed.


Impact
======

Calling `index.php?eID=ExtDirect` will result in an PHP exception.


Affected Installations
======================

Instances using the ExtDirect eID script.

.. index:: JavaScript, Backend, PHP-API
