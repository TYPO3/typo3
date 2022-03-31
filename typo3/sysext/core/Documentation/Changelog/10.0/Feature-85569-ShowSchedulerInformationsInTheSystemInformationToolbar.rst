.. include:: /Includes.rst.txt

==============================================================================
Feature: #85569 - Show scheduler information in the system information toolbar
==============================================================================

See :issue:`85569`

Description
===========

The system information toolbar now shows useful information about the TYPO3 scheduler system extension.

The following information can be gathered quickly via the toolbar:

Warning if the scheduler execution seems not to be configured correctly

Important information about the last successfully executed scheduler run

* the start date
* the start time
* the duration (in minutes)
* the execution type (automatically/CLI or manually/via backend), a manual execution highlights the text

Impact
======

The mentioned scheduler specific information is added to the system information toolbar, if the system extension
`scheduler` is active and if there are any tasks configured at all.

.. index:: Backend, ext:scheduler
