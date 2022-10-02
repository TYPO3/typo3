.. include:: /Includes.rst.txt

.. _breaking-96149:

================================================================
Breaking: #96149 - EXT:form EmailFinisher always uses FluidEmail
================================================================

See :issue:`96149`

Description
===========

In recent versions, the :php:`EmailFinisher` of EXT:form allowed sending
emails with either :php:`StandaloneView` or via :php:`FluidEmail`, which
has been introduced in TYPO3 v10. The :php:`StandaloneView` option has
therefore now been removed together with the :file:`Html.html` and
:file:`Plaintext.html` templates.

Impact
======

Since the EXT:form :php:`EmailFinisher` is now always using :php:`FluidEmail`
for sending emails, the :yaml:`templatePathAndFilename` is not evaluated
anymore. For forms, which still define custom templates with this option,
a fallback kicks in, sending the emails with the default EXT:form
:php:`FluidEmail` templates.

Also the :yaml:`useFluidEmail` configuration option, previously used to
allow a smooth migration path is now obsolete and can safely be removed
from any form finisher configuration.

Affected Installations
======================

Installations, which have not yet switched to :php:`FluidEmail`, while using
custom email templates, configured with :yaml:`templatePathAndFilename`.

Migration
=========

In case you use custom email templates, replace :yaml:`templatePathAndFilename`
with the :yaml:`templateName` and :yaml:`templateRootPaths` options. Also
make sure, you have separate template files for the used formats, e.g.
:file:`ContactForm.html` and :file:`ContactForm.txt`.

.. index:: YAML, NotScanned, ext:form
