
.. include:: ../../Includes.txt

==============================================================================
Feature: #33071 - Add the http header "Content-Language" when rendering a page
==============================================================================

See :issue:`33071`

Description
===========

By default a header "Content-language: XX" is sent where "XX" is the iso code of the
sys_language_content (in the sys_language record, it is represented by the sys_language_isocode field),
if that is properly defined by the sys_language record representing the sys_language_uid.
Setting "config.disableLanguageHeader" disables that.


Impact
======

By default in new and existing installations a header "Content-language: XX" is sent where "XX" is the iso code of the
sys_language_content if that is properly defined by the sys_language record representing the sys_language_uid.
You must set "config.disableLanguageHeader" to disable that and get previous behavior (no header).


.. index:: TypoScript, Frontend
