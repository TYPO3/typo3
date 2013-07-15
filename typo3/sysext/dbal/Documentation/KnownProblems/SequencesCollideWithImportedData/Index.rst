.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _sequences-collide-with-imported-data:

Sequences collide with imported data
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you have imported data into a table that is not running on MySQL,
you may see error messages when inserting a new record (see e.g., bug
#15314 on `http://forge.typo3.org/ <http://forge.typo3.org/issues/15314>`_ ).
This can be avoided by setting the sequence start to a number higher then
the highest ID already in use in all the tables for that handler. See
above for the configuration syntax.
