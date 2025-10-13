..  include:: /Includes.rst.txt

..  _important-107629-1760369226:

================================================================
Important: #107629 - Reference Index check moved to Install Tool
================================================================

See :issue:`107629`

Description
===========

The Reference Index check and update functionality has been moved from the
:guilabel:`System > DB Check` module (EXT:lowlevel) to the :guilabel:`Maintenance`
section of the **Install Tool** (EXT:install).

This change makes this essential administrative tool more accessible and
better organized alongside other common maintenance tasks like database
compare, cache management, and folder structure checks.

Why this change?
----------------

The Reference Index is a critical system component that tracks relationships
between records in TYPO3. Checking and updating it is a routine maintenance
task that administrators perform regularly, similar to:

- Analyzing database structure
- Clearing caches
- Checking folder permissions

Previously, this functionality was hidden in the :guilabel:`DB Check` module of
EXT:lowlevel, which made it:

- **Hard to discover**: Administrators had to know where to look
- **Inconsistent**: Other maintenance tools were in the Install Tool
- **Less accessible**: Required an additional system extension

Impact
======

**For Administrators:**

The Reference Index check and update functionality is now directly available
in the Install Tool under :guilabel:`Maintenance > Check and Update Reference Index`.

Key benefits:

- **Better visibility**: Found alongside other maintenance tools
- **No extra dependencies**: Works out-of-the-box without EXT:lowlevel
- **Consistent location**: All system maintenance tasks in one place
- **Faster access**: Direct access via Install Tool
- **Same functionality**: Check and update operations work exactly as before


**CLI Access:**

The command-line interface remains unchanged and continues to work as before:

..  code-block:: bash

    # Check reference index
    vendor/bin/typo3 referenceindex:update --check

    # Update reference index
    vendor/bin/typo3 referenceindex:update

Migration
=========

No migration is required. Administrators should simply use the new location in
the Install Tool's :guilabel:`Maintenance` section instead of the
:guilabel:`DB Check` module.

The functionality works identically to the previous implementation.

..  index:: Backend, ext:install
