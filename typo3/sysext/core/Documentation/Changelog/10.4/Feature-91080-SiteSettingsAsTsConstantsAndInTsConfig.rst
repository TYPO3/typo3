.. include:: /Includes.rst.txt

=======================================================================
Feature: #91080 - Site settings as TypoScript constants and in TSconfig
=======================================================================

See :issue:`91080`
See :issue:`91081`

Description
===========

Prior to TYPO3 v10.0 it was possible to inject information from
page TSconfig into TypoScript constants with :typoscript:`TSFE.constants.const1 = a`.

This could be used to centralize configuration of e.g. record storagePids,
which could then be used in Backend for modules or for IRRE and for Frontend plugins.

This old feature has been removed, because it was recommended to add site settings.
The according new feature added with TYPO3 v10 was reverted in v10.1 though.

This re-implementation now allows to define site settings via :file:`config/sites/<site-name>/config.yml`

The newly introduced settings inside :file:`config.yml` are made available
as TypoScript constants and page TSconfig constants.

An example configuration in the :file:`config/sites/<site-name>/config.yml`:

.. code-block:: yaml

   settings:
     categoryPid: 658
     styles:
       content:
         loginform:
           pid: 23

This will make these constants available in the template and in page TSconfig:

*  :typoscript:`{$categoryPid}`
*  :typoscript:`{$styles.content.loginform.pid}`

The newly introduced constants for page TSconfig can be used just like constants
in TypoScript.

In page TSconfig this can be used like this:

.. code-block:: typoscript

   # store tx_ext_data records on the given storage page by default (e.g. through IRRE)
   TCAdefaults.tx_ext_data.pid = {$categoryPid}
   # load category selection for plugin from out dedicated storage page
   TCEFORM.tt_content.pi_flexform.ext_pi1.sDEF.categories.PAGE_TSCONFIG_ID = {$categoryPid}


.. note::

   The TypoScript constants are now evaluated in this order:

   #. Global :php:`'defaultTypoScript_constants'`
   #. Site specific settings from the site configuration
   #. Constants from sys_template database records


Impact
======

It is now possible again to have a central place for configuration relevant
for Backend and Frontend.

For instance: It is now possible to define all page-uid related configuration centrally
with the site configuration and get templates and page TSconfig independent
of actual UIDs.

.. index:: TypoScript, ext:core, ext:frontend, ext:backend
