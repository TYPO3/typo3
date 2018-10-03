.. include:: ../../Includes.txt

=============================================================================
Important: #86173 - Location of supplied .htaccess / web.config files changed
=============================================================================

See :issue:`86173`

Description
===========

The location of the former out-of-the-box supplied `_.htaccess` and `_web.config` have been changed and are created
automatically during the TYPO3 installation process.

New location of file templates
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

During a new installation, the files are created automatically. However, there might be situations, where you need
one of the files (e.g. after switching the webserver or for testing purposes). The files can now be found under the
following paths:

* :file:`typo3/sysext/install/Resources/Private/FolderStructureTemplateFiles/root-htaccess` (Apache)
* :file:`typo3/sysext/install/Resources/Private/FolderStructureTemplateFiles/root-web-config` (IIS)

.. index:: ext:install
