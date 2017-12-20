
.. include:: ../../Includes.txt

============================================
Breaking: #63110 - alt_doc_nodoc.php removed
============================================

See :issue:`63110`

Description
===========

Script entry point typo3/alt_doc_nodoc.php and NoDocumentsOpenController class are removed without substitution.

Impact
======

A script pointing to this file resource will trigger a 404 server response and a script instantiating the class will cause a fatal error.

Affected installations
======================

An extension needs to be adapted in the unlikely case that it uses this code.

Migration
=========

Redirecting to typo3/dummy.php instead.


.. index:: PHP-API, Backend
