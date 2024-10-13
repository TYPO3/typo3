.. include:: /Includes.rst.txt

.. _important-105175-1727799093:

=============================================================================
Important: #105175 - Move FrontendBackendUserAuthentication into EXT:frontend
=============================================================================

See :issue:`105175`

Description
===========

The internal :php:`\TYPO3\CMS\Frontend\Authentication\FrontendBackendUserAuthentication`
class, used for frontend requests while being logged in the backend has been
moved from EXT:backend to EXT:frontend, since its dependencies are limited
to EXT:core and EXT:frontend.

While for v13 a class alias mapping and a legacy notation for IDE's is
available, the class is marked as `@internal` and therefore does not fall
under TYPO3's Core API deprecation policy.

.. index:: Frontend, ext:frontend
