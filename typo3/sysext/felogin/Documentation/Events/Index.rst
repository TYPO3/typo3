.. include:: /Includes.rst.txt

.. _psr14events:

=============
PSR-14 events
=============

The following PSR-14 events are available to extend the extension:

BeforeRedirectEvent
===================

Notification before a redirect is made.
:ref:`More details <t3coreapi:BeforeRedirectEvent>`

LoginConfirmedEvent
===================

A notification when a log in has successfully arrived at the plugin, via the
view and the controller, multiple information can be overridden in event
listeners. :ref:`More details <t3coreapi:LoginConfirmedEvent>`

LoginErrorOccurredEvent
=======================

A notification if something went wrong while trying to log in a user.
:ref:`More details <t3coreapi:LoginErrorOccurredEvent>`

LogoutConfirmedEvent
====================

A notification when a log out has successfully arrived at the plugin, via
the view and the controller, multiple information can be overridden in
event listeners. :ref:`More details <t3coreapi:LogoutConfirmedEvent>`

ModifyLoginFormViewEvent
========================

Allows to inject custom variables into the login form.
:ref:`More details <t3coreapi:ModifyLoginFormViewEvent>`

PasswordChangeEvent
===================

Event that contains information about the password which was set,
and is about to be stored in the database. Allows to mark the password
as invalid. :ref:`More details <t3coreapi:PasswordChangeEvent>`

SendRecoveryEmailEvent
======================

Event that contains the email to be sent to the user when they request a
new password. :ref:`More details <t3coreapi:SendRecoveryEmailEvent>`
