.. include:: /Includes.rst.txt

=======================================================================
Feature: #83038 - Introduce Yarn as dependency manager for node modules
=======================================================================

See :issue:`83038`

Description
===========

Because of the broken dependency manager logic in NPM we have introduced
yarn as dependency manager for node modules.

To install node modules you have to install yarn_ first and call
:bash:`yarn install`, do not use :bash:`yarn update` until you really want update a dependency.


Impact
======

npm should not be used to ensure installing the correct versions.


.. _yarn: https://yarnpkg.com/lang/en/

.. index:: JavaScript
