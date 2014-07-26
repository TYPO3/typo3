=========================================
Deprecation: #60559 - makeLoginBoxImage()
=========================================

Description
===========

Method TYPO3\CMS\Backend\Controller::makeLoginBoxImage() is deprecated.


Impact
======

Backend login images are not rendered any longer. The method body is empty and does not return rendered HTML any longer.


Affected installations
======================

The method was unused with default backend login screen for a long time already, an installation is only affected if a
3rd party extension is loaded that changes the default login screen and uses makeLoginBoxImage() or the template marker
LOGINBOX_IMAGE.


Migration
=========

Free an affected 3rd party extension from usage of this method or unload the extension.
