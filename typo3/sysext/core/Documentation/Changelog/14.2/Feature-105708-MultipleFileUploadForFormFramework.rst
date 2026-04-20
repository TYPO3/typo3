..  include:: /Includes.rst.txt

..  _feature-105708-1739721600:

=============================================================
Feature: #105708 - Multiple file upload for EXT:form elements
=============================================================

See :issue:`105708`

Description
===========

The TYPO3 form framework now supports multiple file uploads in the
:yaml:`FileUpload` and :yaml:`ImageUpload` form elements. This allows users
to select and upload multiple files using a single form field.

The implementation follows the same security patterns as Extbase file upload
handling. It uses HMAC-signed deletion requests to ensure secure file removal.

Configuration
-------------

To enable multiple file uploads for a form element, set the :yaml:`multiple`
property to :yaml:`true` in your form definition:

..  code-block:: yaml
    :caption: fileadmin/form_definitions/someForm.yaml
    :emphasize-lines: 14-15,26-27

    type: Form
    identifier: contact-form
    label: 'Contact Form'
    prototypeName: standard
    renderables:
      - type: Page
        identifier: page-1
        label: 'Page 1'
        renderables:
          - type: FileUpload
            identifier: attachments
            label: 'Attachments'
            properties:
              multiple: true
              allowRemoval: true
              saveToFileMount: '1:/user_upload/'
              allowedMimeTypes:
                - application/pdf
                - image/jpeg

          - type: ImageUpload
            identifier: images
            label: 'Images'
            properties:
              multiple: true
              allowRemoval: true
              saveToFileMount: '1:/user_upload/'
              allowedMimeTypes:
                - image/jpeg
                - image/png

The :yaml:`multiple` option is also available in the Form Editor backend
module as a checkbox in the element's inspector panel.

The :yaml:`allowRemoval` property enables users to remove previously uploaded
files before submitting the form. When enabled, a `Remove` checkbox is
displayed next to each uploaded file.

File count validation
---------------------

The existing :yaml:`Count` validator can now be used with
:yaml:`FileUpload` and :yaml:`ImageUpload` elements to limit the number of
uploaded files:

..  code-block:: yaml
    :caption: fileadmin/form_definitions/someForm.yaml

    - type: FileUpload
      identifier: attachments
      label: 'Attachments'
      properties:
        multiple: true
      validators:
        - identifier: Count
          options:
            minimum: 1
            maximum: 5

Frontend rendering
------------------

When :yaml:`multiple` is enabled:

*   The file input field renders with the HTML5 :html:`multiple` attribute
*   Previously uploaded files are displayed in a list with individual remove
    checkboxes
*   Users can select multiple files in the browser's file picker dialog
*   On the summary page, multiple files are displayed as a list

File deletion
-------------

The implementation uses HMAC-signed deletion requests similar to Extbase file
handling. Each uploaded file displays a checkbox that, when checked, marks the
file for removal on form submission. The deletion data is signed with an HMAC
to prevent manipulation.

A new ViewHelper, :html:`<formvh:form.uploadDeleteCheckbox>`, is available
for custom templates:

..  code-block:: html

    <formvh:form.uploadDeleteCheckbox
        property="{element.identifier}"
        fileReference="{file}"
        fileIndex="{iterator.index}"
    />

Adapting custom finishers for multiple file uploads
---------------------------------------------------

When :yaml:`multiple` is enabled on a :yaml:`FileUpload` element, the value
returned by :php:`$formRuntime[$element->getIdentifier()]` is an
:php:`ObjectStorage<FileReference>` instead of a single
:php:`FileReference`. Custom finishers that process file uploads need to be
adapted to handle both cases, single and multiple uploads.

The following example shows the pattern used in the core
:php:`EmailFinisher` and :php:`DeleteUploadsFinisher`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Domain/Finishers/MyFinisher.php

    use TYPO3\CMS\Core\Resource\FileInterface;
    use TYPO3\CMS\Extbase\Domain\Model\FileReference;
    use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
    use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
    use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;

    class MyFinisher extends AbstractFinisher
    {
        protected function executeInternal(): void
        {
            $formRuntime = $this->finisherContext->getFormRuntime();

            foreach (
                $formRuntime->getFormDefinition()->getRenderablesRecursively()
                as $element
            ) {
                if (!$element instanceof FileUpload) {
                    continue;
                }

                $file = $formRuntime[$element->getIdentifier()];

                // Single file upload: value is a FileReference
                if ($file instanceof FileReference) {
                    $this->processFile($file->getOriginalResource());
                }

                // Multiple file upload: value is an ObjectStorage of FileReferences
                if ($file instanceof ObjectStorage) {
                    foreach ($file as $singleFile) {
                        if ($singleFile instanceof FileReference) {
                            $this->processFile(
                                $singleFile->getOriginalResource()
                            );
                        }
                    }
                }
            }
        }

        private function processFile(FileInterface $file): void
        {
            // Your custom logic, e.g. move, copy, attach, etc.
        }
    }

Per-element validation with ObjectStorageElementValidatorInterface
------------------------------------------------------------------

When a form field value is an
:php-short:`\TYPO3\CMS\Extbase\Persistence\ObjectStorage`, for example, a
multiple-file upload, the :php:`ProcessingRule` must decide how to call each
registered validator:

*   **Collection-level validators** (default) receive the entire
    :php-short:`\TYPO3\CMS\Extbase\Persistence\ObjectStorage`. Use this for
    validators that check the collection as a whole, such as
    :php-short:`\TYPO3\CMS\Form\Mvc\Validation\CountValidator` for the minimum
    or maximum number of items.
*   **Element-level validators** receive each item individually. Use this for
    validators that inspect a single item, such as
    :php-short:`\TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator` or
    :php-short:`\TYPO3\CMS\Form\Mvc\Validation\FileSizeValidator`.

To mark a validator as element-level, implement the marker interface
:php-short:`\TYPO3\CMS\Form\Mvc\Validation\ObjectStorageElementValidatorInterface`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Validation/MyPerFileValidator.php

    use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
    use TYPO3\CMS\Form\Mvc\Validation\ObjectStorageElementValidatorInterface;

    final class MyFileValidator extends AbstractValidator implements
        ObjectStorageElementValidatorInterface
    {
        public function isValid(mixed $value): void
        {
            // $value is a single element from the ObjectStorage,
            // e.g. a FileReference - not the whole collection.
        }
    }

For single-value fields, that is, non-
:php-short:`\TYPO3\CMS\Extbase\Persistence\ObjectStorage` values, the
interface has no effect. Validators are always called with the field value
directly.

Impact
======

*   Form integrators can now create forms that accept multiple file uploads
    without custom extensions
*   The :yaml:`FileUpload` and :yaml:`ImageUpload` elements support the new
    :yaml:`multiple` property
*   All existing finishers, `EmailFinisher`, `SaveToDatabaseFinisher`, and
    `DeleteUploadsFinisher`, automatically support multiple file uploads
*   Email templates display multiple files as a list of filenames
*   The summary page displays multiple images as a gallery and multiple files
    as a list of filenames

..  index:: Frontend, ext:form
