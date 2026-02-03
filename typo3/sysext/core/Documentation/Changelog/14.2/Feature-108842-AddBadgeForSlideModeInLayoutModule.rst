..  include:: /Includes.rst.txt

..  _feature-108842-1770128495:

============================================================
Feature: #108842 - Add Badge for Slide Mode in Layout Module
============================================================

See :issue:`108842`

Description
===========

This feature introduces a visual badge in the Layout Module to indicate when
the Slide Mode is active. The badge serves as a clear indicator for editors,
enhancing the user experience by providing immediate feedback on the current
mode of operation.

For each slide mode there is a corresponding badge, with corresponding description.

For slideMode None, no badge is shown.
For slideMode Slide, a badge with the text "Slide" is shown, only if there are currently no content elements in the current page.
For slideMode Collect, a badge with the text "Collect" is shown.
For slideMode CollectReverse, a badge with the text "CollectReverse" is shown.

..  index:: Backend, ext:backend, ext:workspaces
