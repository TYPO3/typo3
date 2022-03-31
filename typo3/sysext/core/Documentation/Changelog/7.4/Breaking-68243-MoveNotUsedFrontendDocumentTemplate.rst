
.. include:: /Includes.rst.txt

=========================================================
Breaking: #68243 - Move not used FrontendDocumentTemplate
=========================================================

See :issue:`68243`

Description
===========

Move unused FrontendDocumentTemplate to ext:compatibility6.


Impact
======

Installations still using FrontendDocumentTemplate require ext:compatibility6 to be installed.


Affected Installations
======================

Installations still using FrontendDocumentTemplate.


Migration
=========

Install ext:compatibility6 or adapt the code to not use the FrontendDocumentTemplate functionality.

Adapting the code is highly recommended.


.. index:: PHP-API, Frontend
