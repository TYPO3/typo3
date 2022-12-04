.. include:: /Includes.rst.txt

.. _feature-93423-1667988850:

==========================================================================
Feature: #93423 - Show warning about duplicated root pages in sites module
==========================================================================

See :issue:`93423`

Description
===========

It might happen that the same root page ID is configured for multiple site
configurations, e.g. in case corresponding files were copied manually. This
might lead to misbehavior, since always the last site with this root page
ID defined is used by TYPO3. As such configuration errors might be hard to
spot, the :guilabel:`Sites` module now informs about such duplications in
the site configuration overview view.

Impact
======

The site module now warns administrators in case the same root page ID is used
in multiple site configurations.

.. index:: Backend, ext:backend
