
.. include:: ../../Includes.txt

============================================================
Deprecation: #69938 - HIDE_L10N_SIBLINGS FlexFormdisplayCond
============================================================

See :issue:`69938`

Description
===========

The flexform `HIDE_L10N_SIBLINGS display` condition has been marked as deprecated and will be removed with CMS 8.
The condition could only be used with translation mode `langChildren=1` to only show the field for the default language.


Impact
======

FlexForms using this condition will show the field separately for each language again.
