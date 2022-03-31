
.. include:: /Includes.rst.txt

======================================================
Feature: #66042 - Web Libraries are included via bower
======================================================

See :issue:`66042`

Description
===========

Web libraries like Bootstrap, jQuery or Font Awesome are now installed via bower package management,
see http://bower.io/ for details on how bower is set up.

All third-party libraries needed to build final CSS or JS files that will be shipped with the core are
excluded from the TYPO3 Core Git and installed via bower when building e.g. a new CSS file out of less.

For setting up a development environment working with web libraries bower (which can be installed via npm
on a local machine) installs all needed dependencies defined in bower.json. The file .bowerrc describes
where the files are put. To set up the third-party libraries and their dependencies, execute the following
command.

.. code-block:: text

	bower install

For updating the code-base to a new version, the bower.json in the root directory can be adapted.
Executing `bower update` will then update the third-party libraries.


Impact
======

Setting up a development environment when working with frontend libraries (e.g. LESS)
requires npm and bower to be installed on the local machine.


.. index:: JavaScript, Backend
