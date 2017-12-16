
.. include:: ../../Includes.txt

=================================================
Feature: #47812 - Query support for BETWEEN added
=================================================

See :issue:`47812`

Description
===========

Support for `between` has been added to the Extbase `Query` object. As there is no performance
advantage to using BETWEEN on the DBMS side (the optimizer converts it to `(min <= expr AND expr <= max)`
this function replicates the DBMS behaviour by building a logical AND condition that has the advantage
of working on all DBMS.

Example:

.. code-block:: php

	$query->matching(
		$query->between('uid', 3, 5)
	);
