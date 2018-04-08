.. include:: ../../Includes.txt

==========================================================================
Deprecation: #84530 - Default values from globals deprecated in FormEngine
==========================================================================

See :issue:`84530`

Description
===========

Setting default values for new database records from GET/POST `defVals` parameter has been marked as deprecated in 9.2
and will be removed in version 10.


Impact
======

If not already provided within the new configuration setting `$result['defaultValues']`, the default values are applied
from GET/POST `defVals` configuration, but will trigger a deprecation warning.


Affected Installations
======================

Installations that use the FormEngine within extensions might need to be changed.


Migration
=========

Use the `defaultValues` configuration to set default values for new database rows
 in the \TYPO3\CMS\Backend\Form\FormDataCompiler::compile call.

.. index:: Backend, PHP-API, NotScanned
