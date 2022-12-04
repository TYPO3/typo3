.. include:: /Includes.rst.txt

.. _important-99044-1668077928:

==================================================================================
Important: #99044 - Ensure auto-created redirect are stored on connected site root
==================================================================================

See :issue:`99044`

Description
===========

Long time ago, automatically created redirects were created on the top root page
:php:`pid=0`, which has been changed meanwhile to create them
using the page ID of the changed page as :sql:`pid` with :issue:`91776`.

This led to some issues, like permissions during copying and pasting pages.

Automatically created redirects are now stored using the root page ID of the
site configurations as :sql:`pid` to minimize side-effect issues and prepare
follow-up features.

.. index:: ext:redirects
