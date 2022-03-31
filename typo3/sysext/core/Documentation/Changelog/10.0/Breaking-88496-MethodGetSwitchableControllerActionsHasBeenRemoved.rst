.. include:: /Includes.rst.txt

=========================================================================
Breaking: #88496 - Method getSwitchableControllerActions has been removed
=========================================================================

See :issue:`88496`

Description
===========

The abstract method :php:`\TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager::getSwitchableControllerActions`
has been removed in favor of :php:`\TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager::getControllerConfiguration`.
While the method name changes, the expected implemented functionality stays the same.


Impact
======

Method :php:`getSwitchableControllerActions` will no longer be called. Instead :php:`getControllerConfiguration` is expected
to be implemented by classes that extend :php:`TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager`.


Affected Installations
======================

All installations that have custom configuration managers that extend :php:`TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager`.


Migration
=========

Rename method :php:`getSwitchableControllerActions` to :php:`getControllerConfiguration` to be TYPO3 >= 10 compatible.

To stay compatible with both version 10 and lower, simply implement both methods and call :php:`getSwitchableControllerActions` from within :php:`getControllerConfiguration`.

Example::

   protected function getSwitchableControllerActions($extensionName, $pluginName)
   {
       return $this->getControllerConfiguration($extensionName, $pluginName);
   }

.. index:: PHP-API, FullyScanned, ext:extbase
