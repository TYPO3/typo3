.. include:: /Includes.rst.txt

===================================================
Deprecation: #87200 - EmailFinisher "format" option
===================================================

See :issue:`87200`

Description
===========

The :yaml:`format` option of the :php:`TYPO3\CMS\Form\Domain\Finishers\EmailFinisher` has been marked as deprecated and
will be removed in TYPO3 11.0. It is replaced by the new :yaml:`addHtmlPart` option which can be used to disable HTML
and enforce plaintext-only mails. If set, mails will contain a plaintext and HTML part, otherwise only a plaintext part.

If the :yaml:`format` option is used, its value will be automatically migrated to :yaml:`addHtmlPart`:

* :yaml:`format: html` becomes :yaml:`addHtmlPart: true`
* :yaml:`format: plaintext` becomes :yaml:`addHtmlPart: false`
* a missing :yaml:`format` becomes :yaml:`addHtmlPart: true`

Opening and saving a form with the form editor once also performs this migration and makes it permanent.


Impact
======

The :yaml:`format` option will no longer work in TYPO3 11.0.


Affected Installations
======================

All installations which use EXT:form and its :php:`TYPO3\CMS\Form\Domain\Finishers\EmailFinisher`.


Migration
=========

Replace :yaml:`format: html` with :yaml:`addHtmlPart: true`.

Replace :yaml:`format: plaintext` with :yaml:`addHtmlPart: false`.

.. index:: YAML, NotScanned, ext:form
