.. include:: ../../Includes.txt

=================================================================
Important: #84910 - Deny direct FAL commands for form definitions
=================================================================

In order to control settings in user provided form definitions files and only
allow manipulations by using the backend form editor (or direct file access
using e.g. SFTP) form file extensions have been changed form simple `.yaml`
to more specific `.form.yaml`.

Direct file commands by using either the backend file list module or implemented
invocations of the file abstraction layer (FAL) API are denied per default and
have to allowed explicitly for the following commands for files ending with the
new file suffix `.form.yaml`:

* plain command invocations

  + create (creating new, empty file having `.form.yaml` suffix)
  + rename (renaming to file having `.form.yaml` suffix)
  + replace (replacing an existing file having `.form.yaml` suffix)
  + move (moving to different file having `.form.yaml` suffix)

* command and content invocations - content signature required

  + add (uploading new file having `.form.yaml` suffix)
  + setContents (changing contents of file having `.form.yaml` suffix)

In order to grant those commands, `\TYPO3\CMS\Form\Slot\FilePersistenceSlot`
has been introduced (singleton instance).

.. code-block:: php

    // Allowing content modifications on a $file object with
    // given $newContent information prior to executing the command

    $slot = GeneralUtility::makeInstance(FilePersistenceSlot::class);
    $slot->allowInvocation(
        FilePersistenceSlot::COMMAND_FILE_SET_CONTENTS,
        $file->getCombinedIdentifier(),
        $this->filePersistenceSlot->getContentSignature($newContent)
    );

    $file->setContents($newContent);

In contrast to *plain command invocations*, those having *content invocations*
(`add` and `setContents`, see list of commands above) require a content signature
as well in order to be executed. The previous example demonstrates that for the
`setContents` command.

Extensions that are modifying (e.g. post-processing) persisted form definition
files using the file abstraction layer (FAL) API need to adjust and extend their
implementation and allow according invocations as outlined above.

See :issue:`84910`
.. index:: Backend, FAL, ext:form
