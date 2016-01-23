.. include:: ../../Includes.txt


.. _reference-rules:

================
Validation Rules
================

Validation rules are a powerful tool to add validation to the form. The
rules function will always be used at the beginning of the form and belongs
to the FORM object.

It is possible to have multiple validation rules for one FORM object, but
the rules have to be added one by one.

**Example**

.. code-block:: typoscript

  rules {
    1 = required
    1 (
      element = first_name
    )
    2 = required
    2 {
      element = last_name
      showMessage = 0
      error = TEXT
      error {
        value = Please enter your last name
      }
    }
    3 = required
    3 {
      element = email_address
    }
    4 = email
    4 {
      element = email_address
    }
  }


When a rule is defined, it will automatically add a message to the object
the rule is connected with. This message will be shown in the local language
and will tell the user the input needs to be according to this rule. The
message can be hidden or overruled with a user defined string/ cObject.

The validation will be done by the order of the rules. The validation can be
stopped when a certain rule is not valid. By default all validation rules
will be processed.

.. toctree::
    :maxdepth: 5
    :titlesonly:
    :glob:

    ValidationAttributes/Index.rst
    Alphabetic/Index.rst
    Alphanumeric/Index.rst
    Between/Index.rst
    Date/Index.rst
    Digit/Index.rst
    Email/Index.rst
    Equals/Index.rst
    Float/Index.rst
    Greaterthan/Index.rst
    Inarray/Index.rst
    Integer/Index.rst
    Ip/Index.rst
    Length/Index.rst
    Lessthan/Index.rst
    Regexp/Index.rst
    Required/Index.rst
    Uri/Index.rst

