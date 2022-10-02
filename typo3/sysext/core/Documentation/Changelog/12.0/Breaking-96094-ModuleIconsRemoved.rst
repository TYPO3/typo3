.. include:: /Includes.rst.txt

.. _breaking-96094:

=======================================
Breaking: #96094 - Module icons removed
=======================================

See :issue:`96094`

Description
===========

The following module icons are removed as they are not needed anymore
by TYPO3 itself. You can find the according icon identifier in parenthesis.

*  :file:`EXT:backend/Resources/Public/Icons/module-about.svg` (`module-about`)
*  :file:`EXT:backend/Resources/Public/Icons/module-contentelements.svg` (`module-contentelements`)
*  :file:`EXT:backend/Resources/Public/Icons/module-cshmanual.svg` (`module-cshmanual`)
*  :file:`EXT:backend/Resources/Public/Icons/module-page.svg` (`module-page`)
*  :file:`EXT:backend/Resources/Public/Icons/module-sites.svg` (`module-sites`)
*  :file:`EXT:backend/Resources/Public/Icons/module-templates.svg` (`module-templates`)
*  :file:`EXT:backend/Resources/Public/Icons/module-urls.svg` (`module-urls`)
*  :file:`EXT:belog/Resources/Public/Icons/module-belog.svg` (`module-belog`)
*  :file:`EXT:beuser/Resources/Public/Icons/module-beuser.svg` (`module-beuser`)
*  :file:`EXT:beuser/Resources/Public/Icons/module-permission.svg` (`module-permission`)
*  :file:`EXT:extensionmanager/Resources/Public/Icons/module-extensionmanager.svg` (`module-extensionmanager`)
*  :file:`EXT:filelist/Resources/Public/Icons/module-filelist.svg` (`module-filelist`)
*  :file:`EXT:form/Resources/Public/Icons/module-form.svg` (`module-form`)
*  :file:`EXT:indexed_search/Resources/Public/Icons/module-indexed_search.svg` (`module-indexed_search`)
*  :file:`EXT:info/Resources/Public/Icons/module-info.svg` (`module-info`)
*  :file:`EXT:lowlevel/Resources/Public/Icons/module-config.svg` (`module-config`)
*  :file:`EXT:lowlevel/Resources/Public/Icons/module-dbint.svg` (`module-dbint`)
*  :file:`EXT:recordlist/Resources/Public/Icons/module-list.svg` (`module-list`)
*  :file:`EXT:recycler/Resources/Public/Icons/module-recycler.svg` (`module-recycler`)
*  :file:`EXT:reports/Resources/Public/Icons/module-reports.svg` (`module-reports`)
*  :file:`EXT:scheduler/Resources/Public/Icons/module-scheduler.svg` (`module-scheduler`)
*  :file:`EXT:setup/Resources/Public/Icons/module-setup.svg` (`module-setup`)
*  :file:`EXT:tstemplate/Resources/Public/Icons/module-tstemplate.svg` (`module-tstemplate`)
*  :file:`EXT:viewpage/Resources/Public/Icons/module-viewpage.svg` (`module-viewpage`)
*  :file:`EXT:workspaces/Resources/Public/Icons/module-workspaces.svg` (`module-workspaces`)

Impact
======

The mentioned icons are removed, any usage by path will result in a broken
image.

Affected Installations
======================

Third-party TYPO3 extensions using these icons.

Migration
=========

Use the already available icon identifiers from `TYPO3.Icons <https://typo3.github.io/TYPO3.Icons/>`_.
The module icons are all registered automatically by the IconRegistry.
In Fluid you can render them by calling :html:`<core:icon identifier="module-icon">`.
In case you need the SVG file directly, download it from the above-mentioned
icon repository page.

.. index:: Backend, NotScanned
