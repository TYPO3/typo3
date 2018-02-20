.. include:: ../../Includes.txt

===============================================
Feature: #83983 - Improved ModuleLinkViewHelper
===============================================

See :issue:`83983`

Description
===========

The class :php:`\TYPO3\CMS\Backend\ViewHelpers\ModuleLinkViewHelper` has been improved by
providing two additional arguments:

- `query`: Allow defining query parameters also as string
- `currentUrlParameterName`: The given argument is filled with the current URL

With this change it is easily possible to migrate existing custom backend route viewhelpers to this one viewhelper.

For example:

Before::

   {bu:editRecord(parameters: 'edit[be_users][{backendUser.uid}]=edit&returnUrl={returnUrl}')}

After ::

   {be:moduleLink(route: 'record_edit', query: 'edit[be_users][{backendUser.uid}]=edit&returnUrl={returnUrl}')}

... and the editRecord ViewHelper of be_user could be deprecated.

.. index:: Backend, ext:backend
