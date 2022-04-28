
.. include:: /Includes.rst.txt

===================================
Feature: #63729 - Introduce GruntJS
===================================

See :issue:`63729`

Description
===========

In order to unify build processes in the backend we introduce
`GruntJS <http://gruntjs.com/>`_ as a central taskrunner. It will
provide a global config that takes over the responsibility
for all build processes in the future starting with the less
files of the backend skin.

The build files are located in the *Build* folder located in the root directory.

For detailed information about setting up GruntJS please head to http://gruntjs.com/.


Initial setup
~~~~~~~~~~~~~

.. code-block:: bash

	npm install
	npm install -g grunt-cli


Registered Tasks
~~~~~~~~~~~~~~~~

Compiling Less files:

.. code-block:: bash

	grunt less


Watching Less files:

.. code-block:: bash

	grunt watch


.. index:: JavaScript
