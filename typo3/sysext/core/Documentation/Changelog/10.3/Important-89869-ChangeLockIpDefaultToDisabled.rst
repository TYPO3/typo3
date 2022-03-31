.. include:: /Includes.rst.txt

===================================================================================
Important: #89869 - Change lockIP default to disabled for both frontend and backend
===================================================================================

See :issue:`89869`

Description
===========

The default setting for the lockIP settings has been changed to disabled. This affects the following four settings:

- FE->lockIP
- FE->lockIPv6
- BE->lockIP
- BE->lockIPv6

While the lockIP feature helps to protect user sessions in some scenarios, the feature also breaks many usage scenarios.
In particular the feature causes random session loss with IPv6 usage because of the Happy eyeballs/Fast fallback algorithm, which causes clients
with IPv6 and IPv4 address support to arbitrarily change between IPv4 and IPv6 based on which connection is established first.

Anyone considering re-enabling lockIP, should be be sure to evaluate any potential issues first, especially when using it with IPv6.

.. index:: Backend, Frontend
