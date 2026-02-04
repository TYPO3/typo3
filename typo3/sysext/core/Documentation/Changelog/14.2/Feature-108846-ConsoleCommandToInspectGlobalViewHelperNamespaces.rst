..  include:: /Includes.rst.txt

..  _feature-108846-1770196894:

==========================================================================
Feature: #108846 - Console command to inspect global ViewHelper namespaces
==========================================================================

See :issue:`108846`

Description
===========

The new console command `fluid:namespaces` has been introduced which lists
all available global ViewHelper namespaces in the current project. This can
be used to verify the current configuration. With the `--json` option, it is
also possible to access that information in a machine-readable way.

Usage:

..  code-block:: bash

    vendor/bin/typo3 fluid:namespaces

Example output:

..  code-block::

    +--------+------------------------------+
    | Alias  | Namespace(s)                 |
    +--------+------------------------------+
    | core   | TYPO3\CMS\Core\ViewHelpers   |
    +--------+------------------------------+
    | formvh | TYPO3\CMS\Form\ViewHelpers   |
    +--------+------------------------------+
    | f      | TYPO3Fluid\Fluid\ViewHelpers |
    |        | TYPO3\CMS\Fluid\ViewHelpers  |
    +--------+------------------------------+


The same information is also available in the Configuration module in the
TYPO3 backend.


Impact
======

The new console command allows developers and integrators to inspect registered
global ViewHelper namespaces in the current project.

..  index:: CLI, Fluid, ext:fluid
