..  include:: /Includes.rst.txt

..  _feature-108975-1770984757:

=============================================================================
Feature: #108975 - Add configuration provider for Extbase class configuration
=============================================================================

See :issue:`108975`

Description
===========

Extbase *class configuration* (persistence mapping) is now exposed in the
backend :guilabel:`System > Configuration` module. The module is available
if the system extension :composer:`typo3/cms-lowlevel` is installed.

The displayed configuration reflects the configured mapping that Extbase uses
at runtime. It is built by collecting and merging all
:file:`EXT:my_extension/Configuration/Extbase/Persistence/Classes.php`
definitions from active packages.

..  seealso::

    *   :ref:`Connecting the model to the database <t3coreapi:extbase-manual-mapping>`

Impact
======

This is a read-only usability improvement. Developers and integrators can
inspect and verify resolved Extbase persistence class mapping such as
extension overrides in the backend, without having to dump configuration
arrays or manually check each
:file:`EXT:my_extension/Configuration/Extbase/Persistence/Classes.php` file.

..  index:: Backend, ext:extbase, ext:lowlevel
