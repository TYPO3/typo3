
.. include:: /Includes.rst.txt

========================================================================
Feature: #69439 - Enhance SQL query reduction in page tree in workspaces
========================================================================

See :issue:`69439`

Description
===========

The process of determining whether a page has workspace versions can be
extended by custom application code utilizing hooks. This way, the meaning
of having versions can be further modified by hooks. For instance the
default behavior of the TYPO3 core is to create a workspace version
record on persisting the same record in the backend - without any
actual changes to the data model.

+ $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService']['hasPageRecordVersions']
  + $parameters['workspaceId']: The submitted workspace ID
  + $parameters['pageId']: The submitted page ID
  + $parameters['versionsOnPageCache']: Reference to the state array
+ $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService']['fetchPagesWithVersionsInTable']
  + $parameters['workspaceId']: The submitted workspace ID
  + $parameters['pageId']: The submitted page ID
  + $parameters['pagesWithVersionsInTable']: Reference to the state array


Impact
======

The hooks introduce the possibility to modify the determined results - only if those hooks are used.

.. index:: Database, LocalConfiguration, ext:workspaces
