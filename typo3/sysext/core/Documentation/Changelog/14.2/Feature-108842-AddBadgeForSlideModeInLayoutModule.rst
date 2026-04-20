..  include:: /Includes.rst.txt

..  _feature-108842-1770128495:

============================================================
Feature: #108842 - Add badge for slide mode in Layout module
============================================================

See :issue:`108842`

Description
===========

This feature introduces a visual badge in the :guilabel:`Content > Layout` module to
indicate when slide mode is active. The badge is a clear indicator for
editors, enhancing the user experience by providing immediate feedback on the
current mode of operation.

Each slide mode has a corresponding badge and description text:

*   For :php:`slideMode = none`, no badge is shown.
*   For :php:`slideMode = slide`, a badge with the text "Slide" is shown, but only
    if there are no content elements on the current page.
*   For :php:`slideMode = collect`, a badge with the text "Collect" is shown.
*   For :php:`slideMode = collectReverse`, a badge with the text "CollectReverse"
    is shown.

..  index:: Backend, ext:backend, ext:workspaces
