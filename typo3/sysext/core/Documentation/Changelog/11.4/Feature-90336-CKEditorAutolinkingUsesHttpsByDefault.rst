.. include:: /Includes.rst.txt

============================================================
Feature: #90336 - CKEditor Autolinking uses https by default
============================================================

See :issue:`90336`

Description
===========

TYPO3 ships with a CKEditor plugin called "autolinking", which
automatically converts typed text within a RTE to an external URL.

When typing `www.typo3.org` this is automatically converted to
an absolute external link, which previously used `http://` as
schema.

Nowadays, over 90% of the web is served via the https protocol
and secure connections via SSL/TLS, where it is safe to
use secure-by-default links.

When not specifically using a schema as prefix for an autolinking
URL, CKEditor now uses `https` instead of `http` as schema by default.


Impact
======

When typing a URL like www.typo3.org in the RTE and the autolinking
plugin is activated, the default schema used is now `https` instead
of `http` for any new links.

However, it is - as before - fully possible to manually change a
link to use the `http://` schema instead.

.. index:: RTE, ext:rte_ckeditor
