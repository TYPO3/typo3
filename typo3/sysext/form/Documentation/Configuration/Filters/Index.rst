.. include:: ../../Includes.txt


.. _reference-filters:

Filters
=======

Add filters to the FORM objects.

It is possible to have multiple filters for one FORM object, but the filters
have to be added one by one.

The submitted data for this particular object will be filtered by the
assigned filters in the given order. The filtered data will be shown to the
visitor when there are errors in the form or on a confirmation page.
Otherwise the filtered data will be send by mail to the receiver.

.. attention::

   By default, all submitted data will be filtered by a Cross Site Scripting
   (XSS) filter to prevent security issues.

.. toctree::
    :maxdepth: 5
    :titlesonly:
    :glob:

    Alphabetic/Index.rst
    Alphanumeric/Index.rst
    Currency/Index.rst
    Digit/Index.rst
    Integer/Index.rst
    Lowercase/Index.rst
    Regexp/Index.rst
    Removexss/Index.rst
    Stripnewlines/Index.rst
    Titlecase/Index.rst
    Trim/Index.rst
    Uppercase/Index.rst

**Example**

The example shown below applies two filters to a FORM object.

- Submitted data: john doe3

- Filtered: John Doe

.. code-block:: typoscript

  filters {
    1 = alphabetic
    1 {
      allowWhiteSpace = 1
    }
    2 = titlecase
  }

