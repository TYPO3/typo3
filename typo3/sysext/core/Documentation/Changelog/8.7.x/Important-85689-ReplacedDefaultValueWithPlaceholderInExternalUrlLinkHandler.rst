.. include:: ../../Includes.txt

========================================================================================
Important: #85689 - Replaced default value with placeholder in external url link handler
========================================================================================

See :issue:`85689`

Description
===========

The :php:`UrlLinkHandler` in EXT:recordlist used a default input value "http://" for external links.
This is not practical because editors often paste a link in the field. This caused broken links due
to duplicate HTTP protocols, for example: `http://https://typo3.org/`

A placeholder is now used instead of a default value. Editors can paste links directly and do not have to remove a default value first.

.. index:: PHP-API, Backend