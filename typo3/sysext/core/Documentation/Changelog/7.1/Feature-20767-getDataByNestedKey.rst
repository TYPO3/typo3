
.. include:: /Includes.rst.txt

===============================================================
Feature: #20767 - Allow nested array access on getData of field
===============================================================

See :issue:`20767`

Description
===========

Right now the `getData` type in TS only allows to access nested arrays in types GPVar and TSFE.
Now the same is allowed for `field` too.

If the field value is :code:`array('somekey' => array('level1' => array('level2' => 'somevalue')));`, you can get the
`somevalue` by configuring the following TypoScript.

.. code-block:: typoscript

	10 = TEXT
	10.data = field:fieldname|level1|level2


Impact
======

You can now access nested keys via getData `field:`. Nested keys are not available with the default set of
content objects, however just content objects and `USER` object may return such a field structure.


.. index:: TypoScript, Frontend
