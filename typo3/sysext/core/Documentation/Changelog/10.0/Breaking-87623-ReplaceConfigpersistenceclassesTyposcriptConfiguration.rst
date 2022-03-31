.. include:: /Includes.rst.txt

==============================================================================
Breaking: #87623 - Replace config.persistence.classes typoscript configuration
==============================================================================

See :issue:`87623`

Description
===========

The configuration of classes in the context of the Extbase persistence is no longer possible via typoscript.
All typoscript concerning the configuration of classes in that context needs to be converted to php, residing
in :file:`EXT:extension/Configuration/Extbase/Persistence/Classes.php`.


Impact
======

Unless converted to php, the configuration in typoscript does no longer have any effect and therefore the following things do no longer work:

- Overwriting table names for models whose table name derived by conventions differ from the desired one.
- The mapping of database field names to model property names
- The definition of model sub classes which is necessary for a proper implementation of single table inheritance.


Affected Installations
======================

All installations that configure persistence related classes via typoscript.


Migration
=========

Every extension that used typoscript for such configuration must provide a php configuration class called:
:file:`EXT:extension/Configuration/Extbase/Persistence/Classes.php`

The migration is best described by an example:

.. code-block:: typoscript

   config.tx_extbase {
       persistence {
           classes {
               TYPO3\CMS\Extbase\Domain\Model\FileMount {
                  mapping {
                     tableName = sys_filemounts
                     columns {
                        title.mapOnProperty = title
                        path.mapOnProperty = path
                        base.mapOnProperty = isAbsolutePath
                     }
                  }
               }
           }
       }
   }

This configuration will look like this, defined in php:

.. code-block:: php

   <?php
   declare(strict_types = 1);

   return [
       \TYPO3\CMS\Extbase\Domain\Model\FileMount::class => [
           'tableName' => 'sys_filemounts',
           'properties' => [
               'title' => [
                   'fieldName' => 'title'
               ],
               'path' => [
                   'fieldName' => 'path'
               ],
               'isAbsolutePath' => [
                   'fieldName' => 'base'
               ],
           ],
       ],
   ];

A few things are noteworthy here:

- The typoscript node :typoscript:`mapping` has been dropped and all sub nodes like :typoscript:`tableName` and :typoscript:`columns` are now located directly
  in the top node, i.e. the class name.
- The mapping of columns changed due to the fact that :typoscript:`mapOnProperty` has been dropped and the mapping direction changed.
  With typoscript the top nodes were called like the class names which indicates the mapping direction model to table. But
  then, one had to define a mapping by columns instead of properties, which means, the mapping directions was reversed,
  forcing you to map database table fields on properties. This was quite confusing and the configuration is now eased as
  one can always think in the model to table mapping direction.
- The load order of these files is determined by the load order of extensions. If multiple extensions override mapping
  configuration of the same extbase domain classes, extension load order should be specified by :file:`ext_emconf.php`
  constraints or dependencies using the :php:`suggests` or :php:`depends` keywords. See
  :ref:`ext_emconf.php file<t3coreapi:extension-declaration>` for details.

.. index:: TypoScript, NotScanned, ext:extbase
