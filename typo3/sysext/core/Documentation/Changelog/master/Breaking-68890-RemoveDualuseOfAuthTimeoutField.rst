=======================================================================================
Breaking: #68890 - Cleanup dual-use of auth_timeout_field in AbstractUserAuthentication
=======================================================================================

Description
===========

In ``AbstractUserAuthentication`` the variable ``auth_timeout_field`` could previously either contain the
name of a field or a timeout-value in seconds. To specify a fieldname the variable can be used as before.
To specify a timeout-value, a new property called ``sessionTimeout`` is introduced that can be set to an integer >= 0.


Impact
======

If some extension reads the value, the default is changed from an integer (0) to an empty string.


Migration
=========

Extensions modifying ``auth_timeout_field`` to a numeric value should switch to using ``sessionTimeout``.
