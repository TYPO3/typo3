.. include:: /Includes.rst.txt

.. _feature-98517-1675861888:

=========================================================
Feature: #98517 - Username in backend password reset mail
=========================================================

See :issue:`98517`

Description
===========

Many users forget their login username and try to login with their email address.
The username of the backend user is now displayed in the password recovery email
alongside the reset link.

Impact
======

The username of the backend user is displayed in the password recovery email
alongside the reset link.

.. note::

   Be aware, this feature comes with security risks:

   Previously, a third-party that gained access to the email account could only
   reset the password of the TYPO3 backend user, but not login if the username
   was different to the email address.

   Now it has all the information needed to login into the TYPO3 backend and
   potentially could cause damage to the website.

   We highly recommend protecting backend accounts using :doc:`MFA <../11.1/Feature-93526-MultiFactorAuthentication>`.

   It is also possible to override the ResetPassword email template to remove
   the username and customize the result.

.. index:: LocalConfiguration, ext:backend
