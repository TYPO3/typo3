.. include:: /Includes.rst.txt

.. _feature-87919-1667984808:

==============================================================
Feature: #87919 - Allow generation of absolute URLs completely
==============================================================

See :issue:`87919`

Description
===========

A new TypoScript option :typoscript:`config.forceAbsoluteUrls = 1` is added.


Impact
======

If set, all links, reference to images or assets, which previously were built with a relative
or absolute path (e.g. :file:`/fileadmin/my-pdf.pdf`) are then rendered as absolute URLs
with the site prefix / current domain. 

Examples for such use-cases are the generation of a full static version of a TYPO3 site
for sending a page via email.

.. index:: TypoScript, ext:frontend