.. include:: /Includes.rst.txt

=========================================
Feature: #94622 - New TCA type "category"
=========================================

See :issue:`94622`

Description
===========

A new TCA field type called :php:`category` has been added to TYPO3 Core.
Its main purpose is to simplify the TCA configuration when adding a category
tree to a record. It therefore supersedes the :php:`\TYPO3\CMS\Core\Category\CategoryRegistry` as well
as the :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility->makeCategorizable()`, which required
creating a "TCA overrides" file.

Both, the :php:`CategoryRegistry` as well as
:php:`ExtensionManagementUtility->makeCategorizable()` are going to be
deprecated in the future.

While using the new type, TYPO3 takes care of generating the necessary TCA
configuration and also adds the database column automatically. Developers
only have to configure the TCA column and add it to the desired record types.

.. code-block:: php

   $GLOBALS['TCA'][$myTable]['columns']['categories'] = [
      'config' => [
         'type' => 'category'
      ]
   ];

   \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($myTable, 'categories');

The above example does not contain the new option :php:`relationship`
since the the default is :php:`manyToMany`. All possible values are:

* :php:`oneToOne`: Stores the uid of the selected category. When using this
  relationship, :php:`maxitems=1` will automatically be added to the column configuration
* :php:`oneToMany`: Stores the uids of selected categories in a comma-separated list
* :php:`manyToMany` (default): Uses the intermediate table :sql:`sys_category_record_mm`
  and only stores the categories count on the local side. This is the use case,
  which was previously accomplished using :php:`ExtensionManagementUtility->makeCategorizable()`.

This means, the new type can not only be used with :php:`relationship=manyToMany` as
a replacement for :php:`makeCategorizable` but can be used for other use
cases too. In case a category tree is required, only allowing one category
to be selected, the necessary configuration reduces to

.. code-block:: php

   $GLOBALS['TCA'][$myTable]['columns']['mainCategory'] = [
      'config' => [
         'type' => 'category',
         'relationship' => 'oneToOne'
      ]
   ];

All other relevant options, e.g. :php:`maxitems=1`, are being set automatically.

Besides :php:`type` and :php:`relationship`, following type specific options
are available:

* :php:`default`
* :php:`exclusiveKeys`: As known from :php:`renderType=selectTree`
* :php:`treeConfig`: As known from :php:`renderType=selectTree`

It's possible to use TSconfig options, such as
:typoscript:`removeItems`. However, adding static items with TSconfig is not
implemented for this type. For such special cases, please continue using TCA
type :php:`select`.

The Override matrix - specifying the options which can be overridden in TSconfig
- is extended for the new type. Following options can be overridden:

* :php:`size`
* :php:`maxitems`
* :php:`minitems`
* :php:`readOnly`
* :php:`treeConfig`

.. note::

   It's still possible to configure a category tree with :php:`type=select`
   and :php:`renderType=selectTree`. This configuration will still work, but
   could in most cases be simplified, using the new :php:`category` TCA type.

Flexform usage
--------------

It's also possible to use the new type in flexform data structures. However,
due to some limitations in flexform, the "manyToMany" relationship is not
supported. Therefore, the default relationship - used if none is defined -
is "oneToMany". This is anyways the most common use case for flexforms,
as it's not important to look from the other side "which flexform elements
reference this category". An example of the "oneToMany" use case is EXT:news,
which allows to only display news of specific categories in the list view.

.. code-block:: xml

    <T3DataStructure>
        <ROOT>
            <TCEforms>
                <sheetTitle>aTitle</sheetTitle>
            </TCEforms>
            <type>array</type>
            <el>
                <categories>
                    <TCEforms>
                        <config>
                            <type>category</type>
                        </config>
                    </TCEforms>
                </categories>
            </el>
        </ROOT>
    </T3DataStructure>

Impact
======

It's now possible to simplify the TCA configuration for category fields,
using the new TCA type :php:`category`.

.. index:: Backend, TCA, ext:backend
