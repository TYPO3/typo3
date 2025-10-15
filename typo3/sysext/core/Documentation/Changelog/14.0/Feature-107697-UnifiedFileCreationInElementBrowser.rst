..  include:: /Includes.rst.txt

..  _feature-107697-1760454867:

===========================================================
Feature: #107697 - Unified file creation in Element Browser
===========================================================

See :issue:`107697`

Description
===========

The file creation functionality in the TYPO3 backend has been significantly
improved and unified to provide a consistent user experience. Previously,
creating new files required navigating to a separate page, which resulted
in context loss and an inconsistent interface compared to folder creation.

The file creation functionality has been migrated to the Element Browser
pattern, similar to how folder creation works. This brings numerous benefits
and improvements to the file management workflow.

Key improvements
----------------

**Unified interface**
    File creation now uses the same modal-based Element Browser interface as
    folder creation, providing a consistent look and feel across file operations.

**Single entry point**
    The new "New File" button provides access to all file creation methods:
    file upload with drag-and-drop, online media (YouTube, Vimeo), and text
    file creation - all in one unified interface.

**Context preservation**
    The modal interface keeps users in context with the file storage tree
    navigation visible, eliminating the need to navigate away from the current
    view.

**Advanced upload handling**
    The new implementation uses the drag-uploader component, which provides
    sophisticated duplication handling (replace, rename, skip, or use existing)
    instead of the basic "override existing" checkbox.

**File extension filtering**
    File creation forms are automatically filtered based on allowed file types
    and hidden when no valid file extensions are available for the current
    context.

**Updated button labels**
    For better consistency and clarity, button labels have been updated from
    "Create Folder" / "Create File" to "New Folder" / "New File", which better
    reflects the nature of the operations.

How to use
==========

In the File List module, click the "New File" button in the document header to
open a modal dialog. This modal provides three ways to add files:

1. **Upload files** - Click "Select & upload files" to choose files.
   The drag-and-drop uploader supports sophisticated duplication handling,
   allowing you to choose whether to replace, rename, skip, or use existing
   files when conflicts occur.

2. **Add online media** - Enter a URL from supported online media platforms
   (YouTube, Vimeo) to add media files directly from the web.

3. **Create text file** - Create new empty text files with supported extensions
   (based on system configuration).

The modal keeps the file storage tree visible, allowing to navigate between
folders without losing context. All three options are available in one unified
interface, streamlining the file creation workflow.

The single "Upload file" button is removed.

Impact
======

The unified file creation interface provides a significantly improved user
experience with better context preservation, advanced duplication handling,
and a consistent modal-based workflow. Editors benefit from having all file
creation options in one place, eliminating the need to navigate between
different pages for different file operations.

The modal-based approach keeps users in context with the file storage tree
always visible, making it easier to work with files across multiple folders
in a single workflow.

..  index:: Backend, ext:filelist
