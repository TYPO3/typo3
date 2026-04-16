.. include:: /Includes.rst.txt

.. _feature-107771-1760360529:

====================================================================
Feature: #107771 - Make rel attribute in external links configurable
====================================================================

See :issue:`107771`

Description
===========

For security reasons, external links that open in a new window should be
generated with :code:`rel="noopener"` to prevent the opened page to have access
to the originating document via JavaScript's :code:`Window.opener` object.

TYPO3's default behavior is to add :code:`rel="noreferrer"` to all such links,
which automatically implies :code:`rel="noopener"` and even is more strict and
not even sends the HTTP "Referer" header to the opened page. This behavior could
be too strict and unwanted for some website owners.

This feature introduces a new TypoScript option :code:`config.linkSecurityRelValue`
to define the :code:`rel` attribute for external links. The default behavior
stays :code:`rel="noreferrer"` but by setting the TypoScript property to
:code:`noopener`, all external links are generated with :code:`rel="noopener"`
instead.

The feature respects existing individual settings per link. All :code:`rel="noopener"`
and :code:`rel="noreferrer"` from other sources are kept.

Impact
======

A new TypoScript configuration option :code:`config.linkSecurityRelValue`
is available and can be set to "noreferrer" (default) to "noopener".

This setting affects all external links with :code:`target="_blank"`.

.. index:: Frontend
