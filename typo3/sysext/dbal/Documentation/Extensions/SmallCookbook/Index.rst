.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _small-cookbook:

Small Cookbook
^^^^^^^^^^^^^^


.. _database-structure:

Database Structure
""""""""""""""""""

MySQL supports ``text`` columns as part of a ``WHERE`` clause using equality
while Oracle for instance does not. This means that if you need to do
something like that:

.. code-block:: sql

	SELECT * FROM tx_ext WHERE column = 'something'

Make sure **not to use** ``text`` as column type but instead use
``varchar(4000)`` which is the limit for Oracle. The other solution is
to use a ``LIKE`` operator:

.. code-block:: sql

	SELECT * FROM tx_ext WHERE column LIKE 'something'


.. _where-clauses:

WHERE Clauses
"""""""""""""

The SQL parser is not as powerful as it could be. Typical problems
occur with calculated conditions such as

.. code-block:: sql

	... WHERE column1 + number1 >= number2


.. _rules-of-thumb:

Rules of thumb
~~~~~~~~~~~~~~

#. The calculated part (``column1 + number1``) **must** start with a column
   name

#. The calculated part may have a column name or a number as second
   operand

#. The part after the operator (``number2``) **must** be a number

#. The calculated part **can only** occur on the left hand of the
   comparison operator

#. More than two operands on the left hand **are not** supported
