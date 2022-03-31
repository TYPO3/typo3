.. include:: /Includes.rst.txt

===========================================================
Feature: #88238 - FeatureToggle: form.legacyUploadMimeTypes
===========================================================

See :issue:`88238`

Description
===========

The feature toggle :code:`form.legacyUploadMimeTypes` makes it possible to enable some predefined :yaml:`allowedMimeTypes` in :yaml:`FileUpload` and :yaml:`ImageUpload` form elements.

These MIME types are enabled through this feature toggle by default as of TYPO3v10 and will be removed completely in TYPO3v11.


Impact
======

Full control over file upload MIME type validation can be achieved by disabling this flag and explicitly listing all allowed MIME types.

.. index:: Frontend, ext:form
