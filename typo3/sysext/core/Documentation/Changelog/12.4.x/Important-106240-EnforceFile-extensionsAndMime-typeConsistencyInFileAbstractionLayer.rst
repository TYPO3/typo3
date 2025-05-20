..  include:: /Includes.rst.txt

..  _important-106240-1747316969:

===============================================================================================
Important: #106240 - Enforce File Extension and MIME-Type Consistency in File Abstraction Layer
===============================================================================================

See :issue:`106240`

Description
===========

The following methods of :php:`ResourceStorage` have been improved to enhance
consistency and security for both existing and uploaded files:

* :php:`addFile`
* :php:`renameFile`
* :php:`replaceFile`
* :php:`addUploadedFile`

Key enhancements
----------------

* Only explicitly allowed file extensions are accepted. These must be configured
  under the following sub-properties in :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']`:
  :php:`textfile_ext`, :php:`mediafile_ext`, or :php:`miscfile_ext`.
* Files are only accepted if their MIME type matches the expected file extension.
  The MIME type is determined based on the actual file content. For example,
  uploading a real PNG image with the filename `image.exe` will be rejected,
  because `image/png` is not a valid MIME type for the `exe` extension.

New Configuration Property in `$GLOBALS['TYPO3_CONF_VARS']['SYS']`
------------------------------------------------------------------

A new configuration property, :php:`miscfile_ext`, has been introduced. It
allows specifying file extensions that don't belong to either `textfile_ext`
or `mediafile_ext`, such as `zip` or `xz`.

New Feature Flags
-----------------

* :php:`security.system.enforceAllowedFileExtensions`:
  Controls whether only the configured file extensions are permitted.
  - **Disabled by default** in existing installations.
  - **Enabled by default** in new installations.
* :php:`security.system.enforceFileExtensionMimeTypeConsistency`:
  Controls whether the MIME type and file extension consistency check
  is enforced.

Exemptions
----------

Some use cases—such as importing files through internal low-level system
components—may require temporary exemptions from the above restrictions.

The following example shows how to define a one-time exemption for a known
and controlled operation:

..  code-block:: php

    <?php
    class ImportCommand
    {
        use \TYPO3\CMS\Core\Resource\ResourceInstructionTrait;

        protected function execute(): void
        {
            // ...

            // Skip the consistency check once for the specified storage, source, and target
            $this->skipResourceConsistencyCheckForCommands($storage, $temporaryFileName, $targetFileName);

            /** @var \TYPO3\CMS\Core\Resource\File $file */
            $file = $storage->addFile($temporaryFileName, $targetFolder, $targetFileName);
        }
    }

..  index:: FAL, LocalConfiguration, ext:core
