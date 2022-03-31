.. include:: /Includes.rst.txt

=====================================================================
Feature: #94406 - Override fileFolder TCA configuration with TSconfig
=====================================================================

See :issue:`94406`

Description
===========

The special `fileFolder configuration options <https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Select/Properties/FileFolder.html#filefolder>`__
for TCA columns of type :php:`select` can be used to fill a select field with files
(images / icons) from a defined folder. This is really handy, e.g. for selecting
predefined icons from a corporate icon set. However, in installations with
multiple sites, such icon sets usually differ from site to site.

Therefore, the :php:`fileFolder` configuration can now be overridden with page
TSconfig, allowing administrators to easily handle those situations by e.g.
using different folders or allowing different file extensions, per site.

To streamline both, the TCA configuration and the corresponding overrides,
the :php:`fileFolder` configuration options have been moved into a dedicated sub
array :php:`fileFolderConfig`, some options have been renamed:

* :php:`fileFolder` option :php:`folder`
* :php:`fileFolder_extList` to :php:`allowedExtensions`
* :php:`fileFolder_recursions` to :php:`depth`

A TCA migration wizard is available, showing where adjustments have to take place.

Before:

.. code-block:: php

   'aField' => [
      'config' => [
         'type' => 'select',
         'renderType' => 'selectSingle',
         'fileFolder' => 'EXT:my_ext/Resources/Public/Icons',
         'fileFolder_extList' => 'svg',
         'fileFolder_recursions' => 1,
      ]
   ]

After:

.. code-block:: php

   'aField' => [
      'config' => [
         'type' => 'select',
         'renderType' => 'selectSingle',
         'fileFolderConfig' => [
            'folder' => 'EXT:styleguide/Resources/Public/Icons',
            'allowedExtensions' => 'svg',
            'depth' => 1,
         ]
      ]
   ]


Thus, the following TSconfig options can be used to overriding their
TCA counterpart:

.. code-block:: typoscript

   config.fileFolderConfig.folder
   config.fileFolderConfig.allowedExtensions
   config.fileFolderConfig.depth

As already known from TCEFORM, those options can be used on various levels

On table level:

.. code-block:: typoscript

   TCEFORM.myTable.myField.config.fileFolderConfig.folder

On table and record type level:

.. code-block:: typoscript

   TCEFORM.myTable.myFiled.types.myType.config.fileFolderConfig.folder

On flex form field level:

.. code-block:: typoscript

   TCEFORM.myTable.pi_flexform.my_ext_pi1.sDEF.myField.config.fileFolderConfig.folder

.. note::

   Except :typoscript:`config.fileFolderConfig.folder`, the new options can not
   only be used to override an existing property, but also to define
   one, which has not yet been configured in TCA.

Impact
======

It's now possible to override the TCA :php:`fileFolder` configuration options
with page TSconfig, allowing administrators to manipulate the available
items on a page basis.

The :php:`fileFolder` TCA configuration is furthermore streamlined and now
encapsulated in a dedicated sub array :php:`fileFolderConfig`.

.. index:: Backend, TCA, TSConfig, ext:backend
