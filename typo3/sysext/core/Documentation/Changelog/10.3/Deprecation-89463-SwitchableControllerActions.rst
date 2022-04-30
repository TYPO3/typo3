.. include:: /Includes.rst.txt

===================================================
Deprecation: #89463 - Switchable Controller Actions
===================================================

See :issue:`89463`

Description
===========

Switchable controller actions have been marked as deprecated and will be removed
in TYPO3 version 12.0.

Switchable controller actions are used to override the allowed set of controllers and actions via TypoScript or plugin
flexforms. While this is convenient for reusing the same plugin for a lot of different use cases, it's also very
problematic as it completely overrides the original configuration defined via
:php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin`.

Switchable controller actions therefore have bad implications that rectify their removal.

First of all, switchable controller actions override the original configuration of plugins at runtime and possibly
depending on conditions which contradicts the idea of :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin`
being the authoritative way to define configuration.

Using the same plugin as an entry point for many different functionalities contradicts the idea of a plugin serving one
specific purpose. Switchable controller actions allow for creating one central plugin that takes care of everything.


Impact
======

All plugins that are using switchable controller actions need to be split into multiple different plugins. Usually, one
would create a new plugin for each possible switchable controller actions configuration entry.


Affected Installations
======================

All installations that make use of switchable controller actions, either via flexform configuration of plugins or via
TypoScript configuration.


Migration
=========

Unfortunately, an automatic migration is not possible. As switchable controller actions allowed to override the whole
configuration of allowed controllers and actions, the only way to migrate is to create dedicated plugins for each former
switchable controller actions configuration entry.

Example:

.. code-block:: xml

   <switchableControllerActions>
      <TCEforms>
         <label>switchable controller actions</label>
         <config>
            <renderType>selectSingle</renderType>
            <items>
               <numIndex index="1">
                  <numIndex index="0">List</numIndex>
                  <numIndex index="1">Product->list</numIndex>
               </numIndex>
               <numIndex index="2">
                  <numIndex index="0">Show</numIndex>
                  <numIndex index="1">Product->show</numIndex>
               </numIndex>
            </items>
         </config>
      </TCEforms>
   </switchableControllerActions>

This configuration would lead to the creation configuration of two different plugins like this:

.. code-block:: php

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'extension',
        'list',
        [
            'Product' => 'list'
        ]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'extension',
        'show',
        [
            'Product' => 'show'
        ]
    );


Advantages of Separate Plugins
------------------------------

When using separate plugins for each switchable controller action combination,
it is possible to properly define which action should be cached.

In addition, TYPO3 v10 LTS allows to group plugins in FormEngine directly
to semantically register various plugins in one specific group.

See :ref:`changelog-Feature-91008-ItemGroupingForTCASelectItems`
for more details.


.. index:: FlexForm, PHP-API, TypoScript, NotScanned, ext:extbase
