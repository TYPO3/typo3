..  include:: /Includes.rst.txt

..  _feature-89951-1741168800:

==============================================================
Feature: #89951 - Cleanup command for form file upload folders
==============================================================

See :issue:`89951`

Description
===========

A new CLI command :bash:`form:cleanup:uploads` has been introduced
to clean up old file upload folders created by the TYPO3 form framework.

When users upload files via :yaml:`FileUpload` or :yaml:`ImageUpload` form
elements, the files are stored in :file:`form_<hash>` subfolders inside
the upload directory. Over time these folders accumulate due
to complete and incomplete form submissions.

Since uploaded files are not moved upon form submission, there is no way to
distinguish between folders from completed and abandoned submissions. The
command identifies form upload folders by their naming pattern, :file:`form_`
followed by exactly 40 hexadecimal characters, and their modification time.
Folders older than a configurable retention period, by default 2 weeks, can be
removed.

You must specify at least one upload folder to scan. Since each form element
can configure a different upload folder via the :yaml:`saveToFileMount`
property, for example :yaml:`1:/user_upload/` or
:yaml:`2:/custom_uploads/`, pass all relevant folders as arguments.

Usage
-----

..  code-block:: bash

    # Dry run: list form upload folders older than 2 weeks (default)
    bin/typo3 form:cleanup:uploads 1:/user_upload/ --dry-run

    # Delete folders older than 48 hours
    bin/typo3 form:cleanup:uploads 1:/user_upload/ --retention-period=48

    # Scan multiple upload folders
    bin/typo3 form:cleanup:uploads 1:/user_upload/ 2:/custom_uploads/

    # Force deletion without confirmation (useful for scheduler tasks)
    bin/typo3 form:cleanup:uploads 1:/user_upload/ --force

    # Verbose output shows details about each folder
    bin/typo3 form:cleanup:uploads 1:/user_upload/ --dry-run -v

Arguments
~~~~~~~~~

:bash:`upload-folder`
    Combined folder identifier or identifiers to scan (required). Multiple
    folders can be specified as separate arguments.

Options
~~~~~~~

:bash:`--retention-period` / :bash:`-r`
    Minimum time in hours before a folder is considered for removal (default:
    336, that is, 2 weeks).

:bash:`--dry-run`
    List expired folders without deleting them.

:bash:`--force` / :bash:`-f`
    Skip the interactive confirmation prompt. This is automatically set
    with :bash:`--no-interaction`, for example, in the TYPO3
    Scheduler.

Scheduler integration
---------------------

The command is available as a Scheduler task since it is
registered via the :php:`#[AsCommand]` attribute. Configure it to run
periodically, for example, once a day or once a week, to keep the upload
folders clean.

Impact
======

The new command provides a safe and configurable way to reclaim disk space
from accumulated form upload folders. The conservative default retention
period of 2 weeks ensures that files belonging to forms still being actively
worked on are not accidentally removed.

..  index:: CLI, ext:form
