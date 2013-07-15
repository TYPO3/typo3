.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt





.. _form-attributes:

Attributes
""""""""""

These attributes can either be assigned to a FORM Object (label,
legend), to a rule or to a filter (error, message) or to a
postprocessor (success, error).


.. _reference-label:

label
~~~~~

The value of the label of the object.

By default the value of the label is a TEXT cObj, but you can use
other cObj as well. When no cObj type is used it assumes you want to
use TEXT. In this case you can assign the value directly to the label
property or indirectly to the value property of the label.

For more information about cObjects, take a look in the document TSREF

**Example:**

::

   label = TEXT
   label {
           value = First name
   }

**Example:**

::

   label = First name

**Example:**

::

   label.value = First name


.. _reference-legend:

legend
~~~~~~

The value of the legend of the object.

By default the value of the label is a TEXT cObj, but you can use
other cObj as well. When no cObj type is used it assumes you want to
use TEXT. In this case you can assign the value directly to the label
property or indirectly to the value property of the label.

For more information about cObjects, take a look in the document TSREF

**Example:**

::

   legend = TEXT
   legend {
           value = Personal information
   }

**Example:**

::

   legend = Personal information

**Example:**

::

   legend.value = Personal information


.. _reference-success:

success
~~~~~~~

Overriding the default text of the confirmation message.

By default the value of the message is a TEXT cObj, but you can use
other cObj as well. When no cObj type is used it assumes you want to
use TEXT. In this case you can assign the value directly to the
message property or indirectly to the value property of the message.

For more information about cObjects, take a look in the document TSREF

**Example:**

::

   success = TEXT
   success {
           value = Thanks for submitting
   }

**Example:**

::

   success = Thanks for submitting

**Example:**

::

   success.value = Thanks for submitting


.. _reference-error:

error
~~~~~

Overriding the default text of the error message, describing the
error.

By default the value of the message is a TEXT cObj, but you can use
other cObj as well. When no cObj type is used it assumes you want to
use TEXT. In this case you can assign the value directly to the
message property or indirectly to the value property of the message.

For more information about cObjects, take a look in the document TSREF

**Example:**

::

   error = TEXT
   error {
           value = The value does not appear to be a hostname
   }

**Example:**

::

   error = The value does not appear to be a hostname

**Example:**

::

   error.value = The value does not appear to be a hostname


.. _reference-message:

message
~~~~~~~

Overriding the default text of the message, describing the rule.

By default the value of the The message is a TEXT cObj, but you can
use other cObj as well. When no cObj type is used it assumes you want
to use TEXT. In this case you can assign the value directly to the
message property or indirectly to the value property of the message.

For more information about cObjects, take a look in the document TSREF

**Example:**

::

   message = TEXT
   message {
           value = Use the right pattern
   }

**Example:**

::

   message = Use the right pattern

**Example:**

::

   message.value = Use the right pattern

