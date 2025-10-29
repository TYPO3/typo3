.. include:: /Includes.rst.txt

.. _feature-107927-1763052530:

==========================================================
Feature: #107927 - Allow relative URIs as system resource
==========================================================

See :issue:`107927`

Description
===========

With the introduction of :ref:`System Resource API <feature-107537-1759136314>`
URIs were considered as one type of resource. However, it was until now
not possible to reference URIs relative to the current host. This however
comes in handy e.g. for PageRenderer or AssetRenderer, which both had the concept
of marking an asset as `external`. Marking assets as `external` allowed for
relative URIs, but also lead to the confusion, that when specifying an absolute
URL with scheme and hostname, the `external` property could be used but could
as well be omitted.

From now on the handling of the `external` property is removed from PageRender
and AssetCollector in favor of introducing a new syntax for URI resources which is
as follows:

* Absolute `https://example.com/path/to/resource.css`

* Relative `URI:/path/to/resource.css`

Like with all resource types, such URI resources can now be specified across
the system, everywhere where a system resource is accepted, not only PageRenderer
or AssetRenderer.

There is one difference to the handling of the `external` properties:

The string after the `URI:` prefix **MUST** be a valid URI. This means,
that TYPO3 will now throw an exception, rather than rendering an invalid URI
to HTML. See :ref:`Remove external attributes <breaking-107927-1763052738>`

Impact
======

URI resources are now first class citizens in all places system resources
can be specified. Not only absolute URLs, but also relative URIs can be specified
by prefixing them with the `URI:` keyword. This makes additional handling,
like `external` flags obsolete. What exactly a system resource is and how it is specified,
can be communicated in a much clearer fashion now e.g. in documentation.

.. index:: ext:core
