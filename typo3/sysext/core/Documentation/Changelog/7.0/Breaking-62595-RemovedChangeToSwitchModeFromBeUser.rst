
.. include:: ../../Includes.txt

===========================================
Breaking: #62595 - Remove SU change-to mode
===========================================

See :issue:`62595`

Description
===========

The permanent user switch has been removed from backend user list for a better UX.


Impact
======

The parameter "emulate" in the view helper "SwitchUser" is dropped. Using the
argument causes an error "Argument "emulate" was not registered".


Affected installations
======================

Any installation using an extension that uses the view helper "SwitchUser" with
"emulate" argument.


Migration
=========

Drop the "emulate" argument in the view helper call.


.. index:: Fluid, Backend
