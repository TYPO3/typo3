.. include:: ../../Includes.txt

====================================================================
Feature: #93988 - Backend module URLs reflect into browser adressbar
====================================================================

See :issue:`93988`

Description
===========

Backend module URLs are now reflected into the browser adressbar, whenever a
backend module or a FormEngine record is opened.

The given URL can be bookmarked or shared with other editors and allows to
re-open the TYPO3 backend with the given context.

A custom Lit-based web componenent router is added which reflects module URLs
into the browser adress bar and at the same time prepares for native web
components to be used as future iframe module alternatives.


Impact
======

Editors can share links to certain records or include these in bug reports.

.. index:: Backend, JavaScript, ext:backend
