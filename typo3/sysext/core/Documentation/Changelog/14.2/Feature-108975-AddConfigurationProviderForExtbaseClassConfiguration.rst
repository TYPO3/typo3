..  include:: /Includes.rst.txt

..  _feature-108975-1770984757:

=============================================================================
Feature: #108975 - Add Configuration Provider for Extbase Class Configuration
=============================================================================

See :issue:`108975`

Description
===========

The Extbase *class configuration* (persistence mapping) is now exposed in the backend
**Configuration** module (**System > Configuration**). The module is available when
the system extension `lowlevel` is installed.

The displayed configuration reflects the configured mapping that Extbase uses at runtime:
it is built by collecting and merging all :file:`Configuration/Extbase/Persistence/Classes.php`
definitions from active packages.

..  seealso::
    :ref:`Connecting the model to the database <t3coreapi:extbase-manual-mapping>`


Impact
======

This is a read-only usability improvement: developers and integrators can inspect and verify
the resolved Extbase persistence class mapping (including extension overrides) directly in the
backend, without dumping configuration arrays or manually checking each
:file:`Configuration/Extbase/Persistence/Classes.php` file.

..  index:: Backend, ext:extbase, ext:lowlevel
