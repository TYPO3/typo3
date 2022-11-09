.. include:: /Includes.rst.txt

.. _important-99044-1668077928:

==================================================================================
Important: #99044 - Ensure auto-created redirect are stored on connected site root
==================================================================================

See :issue:`99044`

Description
===========

Long time back auto-created redirects were created on top root page
:php:`pid=0`, which has been changed meanwhile to create them
using the pageId of the changed page as :sql:`pid` with #91776.

This led to some issues, like permissions during copy-and-pasting pages.

Auto-created redirects are now stored using the siteConfigs rootPageId as
:sql:`pid` to minimize side-effect issues and preparing follup features.

.. index:: ext:redirects
