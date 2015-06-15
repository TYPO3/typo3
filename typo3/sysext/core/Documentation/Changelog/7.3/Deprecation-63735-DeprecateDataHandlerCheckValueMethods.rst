=================================================================
Deprecation: #63735 - Deprecate DataHandler->checkValue_*-methods
=================================================================

Description
===========

The following internal but currently public functions have been marked as deprecated:

* DataHandler->checkValue_text
* DataHandler->checkValue_input
* DataHandler->checkValue_check
* DataHandler->checkValue_radio
* DataHandler->checkValue_group_select
* DataHandler->checkValue_flex


Impact
======

Using these functions will throw a deprecation message.


Migration
=========

These functions are internal and should not be used outside of the core.