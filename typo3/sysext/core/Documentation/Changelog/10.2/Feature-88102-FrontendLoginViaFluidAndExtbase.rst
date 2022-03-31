.. include:: /Includes.rst.txt

===========================================================
Feature: #88102 - Frontend Login Form Via Fluid And Extbase
===========================================================

See :issue:`88102`

Description
===========

The system extension "felogin" now has two plugins. The original plugin which was built via the "PiBase" Plugin
Framework and Marker-based templates continues to work, but is superseded with a new Extbase- and Fluid-based plugin,
allowing to customize templates just like any other modern plugin.

A new feature toggle is introduced to switch between the "PiBase" plugin and the Extbase plugin, which can be switched
in the Install Tool. For existing installations, the default "PiBased" plugin is activated.

Migration
=========

To migrate existing Frontend Login Form plugins an update wizard called "Migrate felogin plugins to use extbase CType"
is provided. The wizard can also be used to switch back from the Extbase version to PiBase.

When using extbase, fluid templates are used to display the depending content. These can be overridden via TypoScript
for customization. All existing templates are found in

EXT:felogin/Resources/Private/Templates/{Login,PasswordRecovery}

Examples
========

Overriding Templates:
All templates are now fluid based, which means they can be overridden by other extensions via typoscript. For example:

To overwrite the :php:`\TYPO3\CMS\FrontendLogin\Controller\LoginController::loginAction()` template with an own one
located in :file:`EXT:my_extension/Resources/Private/Templates/Felogin/Login/Login.html`, the following config will do
the trick.

.. code-block:: typoscript

   plugin.tx_felogin_login {
     view {
       templateRootPaths {
         10 = EXT:my_extension/Resources/Private/Templates/Felogin/
       }
     }
   }

.. index:: Frontend, LocalConfiguration, ext:felogin
