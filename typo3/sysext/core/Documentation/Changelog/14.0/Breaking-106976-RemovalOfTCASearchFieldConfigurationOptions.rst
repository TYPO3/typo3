..  include:: /Includes.rst.txt

..  _breaking-106976-1750865819:

=====================================================================
Breaking: #106976 - Removal of TCA search field configuration options
=====================================================================

See :issue:`106976`

Description
===========

The following TCA field-level search configuration options have been **removed**:

* :php:`search.case`
* :php:`search.pidonly`
* :php:`search.andWhere`

These options were originally intended to customize backend record search
behavior but have proven to be of little practical value:

- They were not used in the TYPO3 Core,
- They were not used in common third-party extensions,
- Their behavior was unclear and inconsistently supported,
- They were insufficiently documented and hard to validate.

This removal is part of the ongoing effort to simplify and streamline TCA
configuration and reduce unnecessary complexity for integrators.

Impact
======

These options are no longer evaluated. They are automatically removed at
runtime through a TCA migration, and a deprecation log entry is generated to
highlight where adjustments are required.

Affected installations
======================

Any installation or extension that defines one or more of these options in its
TCA field configuration:

..  code-block:: diff
    :caption: Example of removed TCA options

     'my_field' => [
         'config' => [
             'type' => 'input',
    -        'search' => [
    -            'case' => true,
    -            'pidonly' => true,
    -            'andWhere' => '{#CType}=\'text\'',
    -        ],
         ],
     ],

Migration
=========

Remove the obsolete :php:`search` options from your TCA field configurations.

..  index:: TCA, FullyScanned, ext:core
