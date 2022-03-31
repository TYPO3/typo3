.. include:: /Includes.rst.txt

======================================================================
Deprecation: #88238 - Allowed MIME types of FileUpload and ImageUpload
======================================================================

See :issue:`88238`

Description
===========

The predefined :yaml:`allowedMimeTypes` of the :yaml:`FileUpload` and :yaml:`ImageUpload` form elements are deprecated and should not be relied on any longer. These will be removed in TYPO3v11.

The "form" extension setup did contain some predefined MIME types for the elements :yaml:`FileUpload` and :yaml:`ImageUpload`:

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               FileUpload:
                 properties:
                   allowedMIMETypes: ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.oasis.opendocument.text', 'application/pdf']

               ImageUpload:
                 properties:
                   allowedMIMETypes: ['image/jpeg', 'image/png', 'image/bmp']


Predefined values like this are used as starting values while the form element is created and later on, values from the form definition are merged.

Thus, a form definition like this:

.. code-block:: yaml

   type: Form
   identifier: test-1
   label: test
   prototypeName: standard
   renderables:
     -
       type: Page
       identifier: page-1
       label: Step
       renderables:
         -
           type: FileUpload
           identifier: fileupload-1
           label: 'File upload'
           properties:
             saveToFileMount: '1:/user_upload/'
             allowedMIMETypes:
               - application/pdf


... resulted in a final form element definition like this:

.. code-block:: yaml

        type: FileUpload
        identifier: fileupload-1
        label: 'File upload'
        properties:
          saveToFileMount: '1:/user_upload/'
          allowedMIMETypes:
            - application/msword
            - application/vnd.openxmlformats-officedocument.wordprocessingml.document
            - application/vnd.oasis.opendocument.text
            - application/pdf


The expected behavior was that only files of type :code:`application/pdf` are accepted, but actually all preconfigured MIME types within the ext:form setup were also valid.

To make the MIME type validation of :yaml:`FileUpload` and :yaml:`ImageUpload` more strict, the preconfigured MIME types have been deprecated and will be removed in TYPO3v11.


Impact
======

The predefined MIME types will be removed in version 11. In version 10 the feature toggle :code:`form.legacyUploadMimeTypes` can be disabled to enforce the new behavior.


Affected Installations
======================

Instances which use the "form" extension with :yaml:`FileUpload` or :yaml:`ImageUpload` form elements.


Migration
=========

Explicitly list all valid MIME types in :yaml:`allowedMimeTypes` within your form definition. Afterwards disable the :code:`form.legacyUploadMimeTypes` feature flag.

.. index:: Frontend, NotScanned, ext:form
