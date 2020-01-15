.. include:: ../../Includes.txt

=================================================
Feature: #90114 - Disable translation of filelist
=================================================

See :issue:`90114`

Description
===========

Currently there is no simple solution to disable/enable the translation of the files in the (BE menu) filelist.

But this could be done programmatically by unsetting languageField in the sys_file_metadata.php like

`unset($GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField']);`


Impact
======

If unsetting
`$GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField']`
then the world toggle button in the (BE) Filelist would disappear which means that translating the metadata the file metadata are not possible anymore. Be aware that existing translations may also become unaccessible, at least via BE.

.. index:: Backend, FAL, TCA, ext:filelist
