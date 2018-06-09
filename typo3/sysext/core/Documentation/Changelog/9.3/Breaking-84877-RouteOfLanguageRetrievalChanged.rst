.. include:: ../../Includes.txt

======================================================
Breaking: #84877 - Route of language retrieval changed
======================================================

See :issue:`84877`

Description
===========

The name of the route that fetches the languages per page has changed.


Impact
======

Calling the old route :php:`languages_page_colpos` will result in a fatal error.


Affected Installations
======================

Every 3rd party extension calling the route is affected.


Migration
=========

Replace the old route name :php:`languages_page_colpos` with :php:`page_languages`.

.. index:: Backend, NotScanned, ext:backend
