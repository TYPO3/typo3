.. include:: /Includes.rst.txt

.. _psr14events:

=============
PSR-14 Events
=============

The following PSR-14 Events are available to extend the extension:

BeforeRedirectEvent
===================

Notification before a redirect is made.

LoginConfirmedEvent
===================

A notification when a log in has successfully arrived at the plugin, via the
view and the controller, multiple information can be overridden in Event
Listeners.

LoginErrorOccurredEvent
=======================

A notification if something went wrong while trying to log in a user.

LogoutConfirmedEvent
====================

A notification when a log out has successfully arrived at the plugin, via
the view and the controller, multiple information can be overridden in
Event Listeners.

ModifyLoginFormViewEvent
========================

Allows to inject custom variables into the login form.

PasswordChangeEvent
===================

Event that contains information about the password which was set,
and is about to be stored in the database. Allows to mark the password
as invalid.

SendRecoveryEmailEvent
======================

Event that contains the email to be sent to the user when they request a
new password.
