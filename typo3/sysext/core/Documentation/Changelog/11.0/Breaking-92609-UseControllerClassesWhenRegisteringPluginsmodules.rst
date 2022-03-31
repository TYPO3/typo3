.. include:: /Includes.rst.txt

==========================================================================
Breaking: #92609 - Use controller classes when registering plugins/modules
==========================================================================

See :issue:`92609`

Description
===========

Configuring plugins and modules via the following methods has changed in two important ways.

* :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin`
* :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule`

Both methods expect to be provided with the arguments :php:`$extensionName` and :php:`$controllerActions`.
:php:`configurePlugin` also allows the argument :php:`$nonCacheableControllerActions`.

The first important change targets the :php:`$extensionName` argument.
During the switch from underscore class names :php:`Tx_Extbase_Foo_Bar` to actual namespaced classes
:php:`TYPO3\CMS\Extbase\Foo\Bar`, a vendor `TYPO3\CMS` has been introduced which had to be respected
during the configuration of plugins. To make that possible the argument :php:`$extensionName` has been
prepended with the vendor name, concatenated with dots.

Before:

.. code-block:: php

   <?php

   \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
       'TYPO3.CMS.Form', // $extensionName
       'Formframework',
       ['FormFrontend' => 'render, perform'],
       ['FormFrontend' => 'perform'],
       \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
   );

Setting the vendor name has been marked as deprecated and must be omitted. Instead, the vendor name will be derived
from the controller class namespace, which leads to the second important change.

Both arguments :php:`$controllerActions` and :php:`$nonCacheableControllerActions` used controller aliases as
array keys. The alias was the controller class name without the namespace and without the :php:`Controller`
suffix. There were a lot of conventions and a custom autoloader mechanism before the introduction
of the composer autoloader, which made it necessary to put controllers in a specific directory and to name
the controller accordingly. As this is no longer the case, there is no need to guess the controller class name
any longer. Instead, the configuration/registration is now done with fully qualified controller class names.

After:

.. code-block:: php

   <?php

   \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
       'Form',
       'Formframework',
       [\TYPO3\CMS\Form\Controller\FormFrontendController::class => 'render, perform'],
       [\TYPO3\CMS\Form\Controller\FormFrontendController::class => 'perform'],
       \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
   );


Impact
======

Using non fully qualified class names during plugin/module registration will lead to malfunctioning plugins/modules at best.
Probably an Exception will be thrown or a fatal error occurs during plugin/module dispatching.


Affected Installations
======================

All installations that use these methods:

* :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin()`
* :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule()`


Migration
=========

* Omit the vendor name in argument :php:`$extensionName`
* Use fully qualified class names as array keys in arguments :php:`$controllerActions` and :php:`$nonCacheableControllerActions`

.. index:: PHP-API, NotScanned, ext:extbase
