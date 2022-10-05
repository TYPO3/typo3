.. include:: /Includes.rst.txt

.. _feature-97816-1664801053:

===========================================================
Feature: #97816 - New AfterTemplatesHaveBeenDeterminedEvent
===========================================================

See :issue:`97816`

Description
===========

With switching to the new TypoScript parser, hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Core/TypoScript/TemplateService']['runThroughTemplatesPostProcessing']`
has been removed.

The new event :php:`AfterTemplatesHaveBeenDeterminedEvent` can be used
to manipulate sys_template rows. The event receives the list of resolved
sys_template rows and the :php:`ServerRequestInterface` and allows manipulating the
sys_template rows array.


Impact
======

The event is called in Backend EXT:tstemplate code, for example in the Template Analyzer,
and - more importantly - in the Frontend.

Extensions using the old hook that want to stay compatible with both core v11 and v12
can implement both.

.. index:: PHP-API, TypoScript, ext:core
