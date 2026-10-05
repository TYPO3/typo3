..  include:: /Includes.rst.txt

..  _howtos-cleanup-upload-folders:

===============================
Cleaning up uploaded form files
===============================

..  versionadded:: 14.2
    :changelog: feature-89951-1741168800

The form elements `FileUpload` and `ImageUpload` store uploaded files in
sub-folders named `form_<hash>`. These folders are placed in the upload folder
that the element property `saveToFileMount` defines, for example
`1:/user_upload/`.

The folders accumulate over time. Submitted forms and abandoned forms both
leave folders behind. The
:ref:`DeleteUploads finisher <concepts-finishers-deleteuploadsfinisher>` only
removes files of successfully submitted forms.

The console command `vendor/bin/typo3 form:cleanup:uploads` removes old form
upload folders. Uploaded files are not moved when a form is submitted. The
command therefore cannot tell folders of submitted and abandoned forms apart.
It removes a folder if both of the following conditions are true:

*   The folder name is `form_` followed by exactly 40 hexadecimal characters.
*   The folder was last modified before the retention period. The default
    retention period is 336 hours (2 weeks).

The command only checks the direct sub-folders of the given upload folders.

..  contents:: Table of contents
    :local:

..  _howtos-cleanup-upload-folders-run:

Run the command
===============

Pass at least one upload folder as a combined folder identifier. If form
elements use different upload folders, pass all of them.

..  code-block:: bash

    # List form upload folders older than 2 weeks without deleting them
    vendor/bin/typo3 form:cleanup:uploads 1:/user_upload/ --dry-run

    # Delete form upload folders older than 48 hours
    vendor/bin/typo3 form:cleanup:uploads 1:/user_upload/ --retention-period=48

    # Scan several upload folders
    vendor/bin/typo3 form:cleanup:uploads 1:/user_upload/ 2:/custom_uploads/

    # Delete without confirmation
    vendor/bin/typo3 form:cleanup:uploads 1:/user_upload/ --force

    # Show the age and the number of files of each folder
    vendor/bin/typo3 form:cleanup:uploads 1:/user_upload/ --dry-run -v

Without `--dry-run` or `--force`, the command asks for confirmation before it
deletes the folders.

..  _howtos-cleanup-upload-folders-reference:

Arguments and options
=====================

..  confval:: upload-folder
    :name: form-cleanup-uploads-upload-folder
    :type: string
    :required: true

    Combined identifier of an upload folder to scan, for example
    `1:/user_upload/`. Pass several folders as separate arguments.

..  confval:: --retention-period / -r
    :name: form-cleanup-uploads-retention-period
    :type: integer
    :default: `336`

    Minimum age in hours before a form upload folder is removed. The value
    must be at least `1`.

..  confval:: --dry-run
    :name: form-cleanup-uploads-dry-run
    :type: bool
    :default: `false`

    Lists the expired folders without deleting them.

..  confval:: --force / -f
    :name: form-cleanup-uploads-force
    :type: bool
    :default: `false`

    Skips the confirmation question. The command sets this option
    automatically when it runs without interaction, for example with
    `--no-interaction` or in the scheduler.

..  _howtos-cleanup-upload-folders-scheduler:

Run the command regularly
=========================

Run the command regularly to keep the upload folders clean, for example once a
day or once a week. Use one of the following options:

*   Create a task of type :guilabel:`Execute console commands` in the
    scheduler and select `form:cleanup:uploads`.
*   Add the command to a cron job on the server.
