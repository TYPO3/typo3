
.. include:: ../../Includes.txt

=============================================================================
Breaking: #77049 - Remove unused properties from SuggestWizardDefaultReceiver
=============================================================================

See :issue:`77049`

Description
===========

The following unused properties have been removed from the :php:`SuggestWizardDefaultReceiver` class:

- :php:`selectClause`
- :php:`addWhere`


Impact
======

Extensions which use one of the protected properties above will not work properly as the
properties are not used by class methods anymore.


Affected Installations
======================

All installations with a 3rd party extension extending the :php:`SuggestWizardDefaultReceiver` class.


Migration
=========

Don't set the properties in extended classes and make use of the constructor and queryTable
method instead, as it is done in the :php:`SuggestWizard` class.

.. index:: PHP-API, Backend
