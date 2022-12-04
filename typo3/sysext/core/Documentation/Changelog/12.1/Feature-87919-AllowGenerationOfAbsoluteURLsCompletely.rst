.. include:: /Includes.rst.txt

.. _feature-87919-1667984808:

==============================================================
Feature: #87919 - Allow generation of absolute URLs completely
==============================================================

See :issue:`87919`

Description
===========

A new TypoScript option :typoscript:`config.forceAbsoluteUrls = 1` has been added.


Impact
======

If the option is set, all links, references to images or assets previously built with a relative
or absolute path (e.g. :file:`/fileadmin/my-pdf.pdf`) will be rendered as absolute URLs
with the site prefix / current domain.

Examples for such use cases are the generation of a full static version of a TYPO3 site
for sending a page via email.

.. index:: TypoScript, ext:frontend
