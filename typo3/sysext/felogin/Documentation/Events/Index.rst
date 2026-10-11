.. include:: /Includes.rst.txt

.. _psr14events:

=============
PSR-14 events
=============

The following PSR-14 events are available to extend the extension:

..  _psr14events-afteruserloggedinevent:

AfterUserLoggedInEvent
======================

Trigger any kind of action when a frontend user has been successfully logged in.
:ref:`More details <t3coreapi:AfterUserLoggedInEvent>`

..  _psr14events-beforeredirectevent:

BeforeRedirectEvent
===================

Notification before a redirect is made.
:ref:`More details <t3coreapi:BeforeRedirectEvent>`

..  _psr14events-loginconfirmedevent:

LoginConfirmedEvent
===================

A notification when a log in has successfully arrived at the plugin, via the
view and the controller, multiple information can be overridden in event
listeners. :ref:`More details <t3coreapi:LoginConfirmedEvent>`

..  _psr14events-loginerroroccurredevent:

LoginErrorOccurredEvent
=======================

A notification if something went wrong while trying to log in a user.
:ref:`More details <t3coreapi:LoginErrorOccurredEvent>`

..  _psr14events-logoutconfirmedevent:

LogoutConfirmedEvent
====================

A notification when a log out has successfully arrived at the plugin, via
the view and the controller, multiple information can be overridden in
event listeners. :ref:`More details <t3coreapi:LogoutConfirmedEvent>`

..  _psr14events-modifyloginformviewevent:

ModifyLoginFormViewEvent
========================

Allows to inject custom variables into the login form.
:ref:`More details <t3coreapi:ModifyLoginFormViewEvent>`

..  _psr14events-passwordchangeevent:

PasswordChangeEvent
===================

Event that contains information about the password which was set,
and is about to be stored in the database.
:ref:`More details <t3coreapi:PasswordChangeEvent>`

..  _psr14events-sendrecoveryemailevent:

SendRecoveryEmailEvent
======================

Event that contains the email to be sent to the user when they request a
new password. :ref:`More details <t3coreapi:SendRecoveryEmailEvent>`
