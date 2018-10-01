
.. include:: ../../Includes.txt

=======================================================
Feature: #66047 - Introduce JavaScript notification API
=======================================================

See :issue:`66047`

Description
===========

The Flashmessages API has been moved from `TYPO3.Flashmessages` to `top.TYPO3.Flashmessages` in TYPO3 CMS 7.0.

Now we introduce the new JavaScript Notification API and remove the refactoring of the FlashMessage API which was made for TYPO3 CMS 7.0.

The compatibility layer for TYPO3.FlashMessage has changed to use the new Notification API and will be removed with TYPO3 v9 as before.

The new Notification API works similar to old Flashmessages, you can use it from the Top-Frame, where is it loaded one time for the complete backend.

Please look at the examples section in this document for more details.


Migration
=========

The affected 3rd party extensions must be modified to use `top.TYPO3.Notification` instead of `top.TYPO3.Flashmessages`.

Examples:

1) Old and new syntax in general

.. code-block:: javascript

	// Old and deprecated:
	top.TYPO3.Flashmessages.display(TYPO3.Severity.notice)

	// New and the only correct way:
	top.TYPO3.Notification.notice(title, message)


2) Notice notification

.. code-block:: javascript

	// duration is optional, default is 5 seconds
	top.TYPO3.Notification.notice(title, message, duration)


3) Info notification

.. code-block:: javascript

	// duration is optional, default is 5 seconds
	top.TYPO3.Notification.info(title, message, duration)


4) Success notification

.. code-block:: javascript

	// duration is optional, default is 5 seconds
	top.TYPO3.Notification.success(title, message, duration)


5) Warning notification

.. code-block:: javascript

	// duration is optional, default is 5 seconds
	top.TYPO3.Notification.warning(title, message, duration)


6) Error notification

.. code-block:: javascript

	// duration is optional, default is 0 seconds which means sticky!
	top.TYPO3.Notification.error(title, message, duration)


.. index:: JavaScript, Backend
