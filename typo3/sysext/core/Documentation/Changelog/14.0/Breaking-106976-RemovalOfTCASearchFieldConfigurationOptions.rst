..  include:: /Includes.rst.txt

..  _breaking-106976-1750865819:

=====================================================================
Breaking: #106976 - Removal of TCA search field configuration options
=====================================================================

See :issue:`106976`

Description
===========

The following TCA field  level search configuration options have been **removed**:

* :php:`search.case`
* :php:`search.pidonly`
* :php:`search.andWhere`

These options were originally intended to customize backend record search
behavior, but they have proven to be of little practical value:

- They were not used in TYPO3 Core,
- They are not used in common third-party extensions,
- They had unclear behavior and inconsistent support,
- They were insufficiently documented and hard to validate.

The removal is part of an ongoing effort to simplify and streamline the TCA
configuration and reduce cognitive load for integrators.

Impact
======

Any of those option are no longer evaluated. They are automatically removed at
runtime through a TCA migration, and a deprecation log entry is generated to
highlight where adjustments are required.

Affected Installations
======================

Any installation or extension that uses any of those options in their TCA
field configuration:

.. code-block:: php

   'my_field' => [
       'config' => [
           'type' => 'input',
           'search' => [
               'case' => true,
               'pidonly' => true,
               'andWhere' => '{#CType}=\'text\'',
           ],
       ],
   ],

Migration
=========

Remove the obsolete :php:`search` options from your TCA field configurations.

..  index:: TCA, FullyScanned, ext:core
