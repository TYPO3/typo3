.. include:: ../../../Includes.txt


.. _reference-filters-titlecase:

=========
titlecase
=========

Returns the incoming value with all alphabetic characters converted to title
case. Alphabetic is determined by the Unicode character properties.

**Example**

- Submitted data: kasper skårhøj

- Filtered: Kasper Skårhøj

.. code-block:: typoscript

  filters {
    1 = titlecase
  }

