
.. include:: ../../Includes.txt

======================================================================================
Breaking: #63069 - Removed compatibility layer for submodules of func and info modules
======================================================================================

See :issue:`63069`

Description
===========

The web_info and web_func modules use the module dispatcher now and do not have
their own index scripts.
Therefore any submodule for those modules need to adjust links accordingly.


Impact
======

Any third party code creating links to either web_info or web_func module using the old entry scripts,
will not work anymore.


Migration
=========

Use :code:`BackendUtility::getModuleUrl()` instead to get the correct target for your links.
