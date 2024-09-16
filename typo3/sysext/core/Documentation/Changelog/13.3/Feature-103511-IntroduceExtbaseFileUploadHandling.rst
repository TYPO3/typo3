.. include:: /Includes.rst.txt

.. _feature-103511-1711894330:

======================================================================
Feature: #103511 - Introduce Extbase file upload and deletion handling
======================================================================

See :issue:`103511`

Description
===========

TYPO3 now provides an API for file upload- and deletion-handling in Extbase
extensions, which allows extension developers to implement file uploads
more easily into Extbase Domain Models.

The scope of this API is to cover some of the most common use cases and to
keep the internal file upload and deletion process in Extbase as simple as
possible.

The API supports mapping and handling of file uploads and deletions for the
following scenarios:

*   Property of type :php-short:`\TYPO3\CMS\Core\Resource\FileReference` in a domain model
*   Property of type :php:`ObjectStorage<FileReference>` in a domain model

File uploads can be validated by the following rules:

*   minimum and maximum file count
*   minimum and maximum file size
*   allowed MIME types
*   image dimensions (for image uploads)

Additionally, it is ensured, that the filename given by the client is valid,
meaning that no invalid characters (null-bytes) are added and that the file
does not contain an invalid file extension. The API has support for custom
validators, which can be created on demand.

To avoid complexity and maintain data integrity, a file upload is only
processed if the validation of all properties of a domain model is successful.
In this first implementation, file uploads are not persisted/cached temporarily,
so this means in any case of a validation failure ("normal" validators and file upload
validation) a file upload must be performed again by users.

Possible future enhancements of this functionality could enhance the existing
`#[FileUpload]` attribute/annotation with configuration like a temporary storage
location, or specifying additional custom validators (which can be done via the PHP-API as
described below)

Nesting of domain models
------------------------

File upload handling for nested domain models (e.g. modelA.modelB.fileReference)
is not supported.


File upload configuration with the `FileUpload` attribute
---------------------------------------------------------

File upload for a property of a domain model can be configured using the
newly introduced :php:`\TYPO3\CMS\Extbase\Annotation\FileUpload` attribute.

Example:

..  code-block:: php

    #[FileUpload([
        'validation' => [
            'required' => true,
            'maxFiles' => 1,
            'fileSize' => ['minimum' => '0K', 'maximum' => '2M'],
            'allowedMimeTypes' => ['image/jpeg', 'image/png'],
        ],
        'uploadFolder' => '1:/user_upload/files/',
    ])]
    protected ?FileReference $file = null;

All configuration settings of the
:php:`\TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration` object can
be defined using the :php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload`
attribute. It is however not possible
to add custom validators using the
:php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload` attribute, which you
can achieve with a manual configuration as shown below.

The currently available configuration array keys are:

*   `validation` (:php:`array` with keys `required`, `maxFiles`, `minFiles`,
    `fileSize`, `allowedMimeTypes`, `imageDimensions`, see
    :ref:`83749-validationkeys`)
*   `uploadFolder` (:php:`string`, destination folder)
*   `duplicationBehavior` (:php:`object`, behaviour when file exists)
*   `addRandomSuffix` (:php:`bool`, suffixing files)
*   `createUploadFolderIfNotExist` (:php:`bool`, whether to create missing
    directories)

It is also possible to use the :php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload` annotation to configure
file upload properties, but it is recommended to use the
:php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload` attribute due to better readability.


Manual file upload configuration
--------------------------------

A file upload configuration can also be created manually and should be
done in the :php:`initialize*Action`.

Example:

..  code-block:: php

    public function initializeCreateAction(): void
    {
        $mimeTypeValidator = GeneralUtility::makeInstance(MimeTypeValidator::class);
        $mimeTypeValidator->setOptions(['allowedMimeTypes' => ['image/jpeg']]);

        $fileHandlingServiceConfiguration = $this->arguments->getArgument('myArgument')->getFileHandlingServiceConfiguration();
        $fileHandlingServiceConfiguration->addFileUploadConfiguration(
            (new FileUploadConfiguration('myPropertyName'))
                ->setRequired()
                ->addValidator($mimeTypeValidator)
                ->setMaxFiles(1)
                ->setUploadFolder('1:/user_upload/files/')
        );

        $this->arguments->getArgument('myArgument')->getPropertyMappingConfiguration()->skipProperties('myPropertyName');
    }


Configuration options for file uploads
--------------------------------------

The configuration for a file upload is defined in a
:php:`FileUploadConfiguration` object.

This object contains the following configuration options.

..  hint::

    The appropriate setter methods or configuration
    keys can best be inspected inside that class definition.

Property name:
~~~~~~~~~~~~~~

Defines the name of the property of a domain model to which the file upload
configuration applies. The value is automatically retrieved when using
the :php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload` attribute. If the
:php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration` object
is created manually, it must be set using the :php:`$propertyName`
constructor argument.

Validation:
~~~~~~~~~~~

File upload validation is defined in an array of validators in the
:php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration` object. The validator
:php:`\TYPO3\CMS\Extbase\Validation\Validator\FileNameValidator`,
which ensures that no executable PHP files can
be uploaded, is added by default if the file upload configuration object
is created using the
:php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload` attribute.

In addition, Extbase includes the following validators to validate an
:php-short:`\TYPO3\CMS\Core\Http\UploadedFile` object:

*   :php:`\TYPO3\CMS\Extbase\Validation\Validator\FileSizeValidator`
*   :php:`\TYPO3\CMS\Extbase\Validation\Validator\MimeTypeValidator`
*   :php:`\TYPO3\CMS\Extbase\Validation\Validator\ImageDimensionsValidator`

Those validators can either be configured with the
:php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload` attribute or added
manually to the configuration object
with the :php:`addValidator` method.

Required:
~~~~~~~~~

Defines whether a file must be uploaded. If it is set to `true`, the
:php:`minFiles` configuration is set to `1`.

Minimum files:
~~~~~~~~~~~~~~

Defines the minimum amount of files to be uploaded.

Maximum files:
~~~~~~~~~~~~~~

Defines the maximum amount of files to be uploaded.

Upload folder:
~~~~~~~~~~~~~~

Defines the upload path for the file upload. This configuration expects a
storage identifier (e.g. :php:`1:/user_upload/folder/`). If the given target
folder in the storage does not exist, it is created automatically.

Upload folder creation, when missing:
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The default creation of a missing storage folder can be disabled via the
configuration attribute :php:`createUploadFolderIfNotExist`
(:php:`bool`, default :php:`true`).

Add random suffix:
~~~~~~~~~~~~~~~~~~

When enabled, the filename of an uploaded and persisted file will contain a
random 16 char suffix. As an example, an uploaded file named
:php:`job-application.pdf` will be persisted as
:php:`job-application-<random-hash>.pdf` in the upload folder.

The default value for this configuration is :php:`true` and it is recommended
to keep this configuration active.

This configuration only has an effect when uploaded files are persisted.

Duplication behavior:
~~~~~~~~~~~~~~~~~~~~~

Defines the FAL behavior, when a file with the same name exists in the target
folder. Possible values are :php:`DuplicationBehavior::RENAME` (default),
:php:`DuplicationBehavior::REPLACE` and :php:`DuplicationBehavior::CANCEL`.


Modifying existing configuration
--------------------------------

File upload configuration defined by the
:php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload` attribute can be
changed in the :php:`initialize*Action`.

Example:

..  code-block:: php

    public function initializeCreateAction(): void
    {
        $validator = GeneralUtility::makeInstance(MyCustomValidator::class);

        $argument = $this->arguments->getArgument('myArgument');
        $configuration = $argument->getFileHandlingServiceConfiguration()->getFileUploadConfigurationForProperty('file');
        $configuration?->setMinFiles(2);
        $configuration?->addValidator($validator);
        $configuration?->setUploadFolder('1:/user_upload/custom_folder');
    }

The example shows how to modify the file upload configuration for the argument
:php:`item` and the property :php:`file`. The minimum amount of files to be
uploaded is set to :php:`2` and a custom validator is added.

To remove all defined validators except the :php:`DenyPhpUploadValidator`, use
the :php:`resetValidators()` method.


Using TypoScript configuration for file uploads configuration
-------------------------------------------------------------

When a file upload configuration for a property has been added using the
:php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload` attribute, it may be
required make the upload folder or
other configuration options configurable with TypoScript.

Extension authors should use the :php:`initialize*Action` to apply settings
from TypoScript to a file upload configuration.


Example:

..  code-block:: php

    public function initializeCreateAction(): void
    {
        $argument = $this->arguments->getArgument('myArgument');
        $configuration = $argument->getFileHandlingServiceConfiguration()->getConfigurationForProperty('file');
        $configuration?->setUploadFolder($this->settings['uploadFolder'] ?? '1:/fallback_folder');
    }


..  _83749-validationkeys:

File upload validation
----------------------

Each uploaded file can be validated against a configurable set of validators.
The :php:`validation` section of the :php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload` attribute allows to
configure commonly used validators using a configuration shorthand.

The following validation rules can be configured in the :php:`validation`
section of the :php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload` attribute:

*   :php:`required`
*   :php:`minFiles`
*   :php:`maxFiles`
*   :php:`fileSize`
*   :php:`allowedMimeTypes`
*   :php:`imageDimensions`

Example:

..  code-block:: php

    #[FileUpload([
        'validation' => [
            'required' => true,
            'maxFiles' => 1,
            'fileSize' => ['minimum' => '0K', 'maximum' => '2M'],
            'allowedMimeTypes' => ['image/jpeg'],
            'imageDimensions' => ['maxWidth' => 4096, 'maxHeight' => 4096]
        ],
        'uploadFolder' => '1:/user_upload/extbase_single_file/',
    ])]

Extbase will internally use the Extbase file upload validators for
:php:`fileSize`, :php:`allowedMimeTypes` and :php:`imageDimensions` validation.

Custom validators can be created according to project requirements and must
extend the Extbase :php-short:`\TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator`.
The value to be validated is
always a PSR-7 :php-short:`\TYPO3\CMS\Core\Http\UploadedFile` object.
Custom validators can however not
be used in the :php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload` attribute
and must be configured manually.


Deletion of uploaded files and file references
----------------------------------------------

The new Fluid ViewHelper
:ref:`Form.uploadDeleteCheckbox ViewHelper <f:form.uploadDeleteCheckbox> <t3viewhelper:typo3-fluid-form-uploaddeletecheckbox>`
can be used to show a "delete file" checkbox in a form.

Example for object with :php-short:`\TYPO3\CMS\Core\Resource\FileReference` property:

..  code-block:: php

    <f:form.uploadDeleteCheckbox property="file" fileReference="{object.file}" />

Example for an object with an :php:`ObjectStorage<FileReference>` property,
containing multiple files and allowing to delete the first one
(iteration is possible within Fluid, to do that for every object of the collection):

..  code-block:: php

    <f:form.uploadDeleteCheckbox property="file.0" fileReference="{object.file}" />

Extbase will then handle file deletion(s) before persisting a validated
object. It will:

*   validate that minimum and maximum file upload configuration for the affected
    property is fulfilled (only if the property has a :php-short:`\TYPO3\CMS\Extbase\Annotation\FileUpload`)
*   delete the affected :php:`sys_file_reference` record
*   delete the affected file

Internally, Extbase uses :php:`FileUploadDeletionConfiguration` objects to track
file deletions for properties of arguments. Files are deleted directly without
checking whether the current file is referenced by other objects.

Apart from using this ViewHelper, it is of course still possible to manipulate
:php-short:`\TYPO3\CMS\Core\Resource\FileReference` properties with custom logic before persistence.

New PSR-14 events
-----------------

The following new PSR-14 event has been added to allow customization
of file upload related tasks:

ModifyUploadedFileTargetFilenameEvent
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The :php-short:`\TYPO3\CMS\Extbase\Event\Service\ModifyUploadedFileTargetFilenameEvent`
allows event listeners to
alter a filename of an uploaded file before it is persisted.

Event listeners can use the method `getTargetFilename()` to retrieve the filename
used for persistence of a configured uploaded file. The filename can then be
adjusted via `setTargetFilename()`. The relevant configuration can be retrieved
via `getConfiguration()`.

Impact
======

Extension developers can use the new feature to implement file uploads and
file deletions in Extbase extensions easily with commonly known Extbase
property attributes/annotations.

.. index:: PHP-API, ext:extbase
