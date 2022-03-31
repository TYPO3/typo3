
.. include:: /Includes.rst.txt

========================================================================
Feature: #74109 - Set the alternative Backend Logo via Extension Manager
========================================================================

See :issue:`74109`

Description
===========

The Backend Logo in the upper left corner can now be configured in the Extension Configuration of EXT:backend
within the Extension Manager. A relative path to the TYPO3 installation ("PATH_site"), e.g. "fileadmin/myfile.jpg"
or a path to an extension, e.g. "EXT:my_theme/Resources/Public/Icons/Logo.png" can be configured there.

The configuration option within the Backend extension (EXT:backend) is called `backendLogo`.


Impact
======

The previously available `$GLOBALS[TBE_STYLES][logo]` option has no effect anymore.

.. index:: Backend
