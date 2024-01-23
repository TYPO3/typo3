.. include:: /Includes.rst.txt

.. _feature-102072-1696076672:

================================================================
Feature: #102072 - Allow redirect filtering by "protected" state
================================================================

See :issue:`102072`

Description
===========

The :guilabel:`Redirects` administration module now allows to filter redirects
based on the :php:`protected` state. Additionally, protected redirects now
show a lock icon in the list of redirects, so it is better visualized, that a
redirect is protected and excluded from automatic deletion (for example, with
`redirects:cleanup`).


Impact
======

The administration of redirects has become more user-friendly, because users
can now easily filter protected redirects in the :guilabel:`Redirects`
administration module.

.. index:: ext:redirects
