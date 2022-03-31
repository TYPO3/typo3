.. include:: /Includes.rst.txt

===========================================
Breaking: #91562 - cObject TEMPLATE removed
===========================================

See :issue:`91562`

Description
===========

The cObject :typoscript:`TEMPLATE`, used for rendering marker-based templates
has been removed along with the PHP class :php:`TYPO3\CMS\Frontend\ContentObject\TemplateContentObject`.

The successor :typoscript:`FLUIDTEMPLATE` is widely used since TYPO3 v7,
and acts as a replacement for marker-based templates.


Impact
======

Using TypoScript with :typoscript:`page.10 = TEMPLATE` will result in a PHP
error when rendering the frontend.

Referencing the PHP class will result in a fatal PHP error.


Affected Installations
======================

TYPO3 installation still using :typoscript:`TEMPLATE` cObject in their TypoScript.


Migration
=========

Refactor TypoScript templates to not use the cObject :typoscript:`TEMPLATE` anymore.

In case you can not or want not make the switch to :typoscript:`FLUIDTEMPLATE`, install
the extension `modern_template_building` from the official
TYPO3 Extension Repository at https://extensions.typo3.org/, which acts as a drop-in replacement, and also ships the cObject :typoscript:`FILE`
which is highly useful for :typoscript:`TEMPLATE` cObjects.

The extension is compatible with TYPO3 v9+.

.. index:: TypoScript, FullyScanned, ext:frontend
