.. include:: ../../Includes.txt


.. _reference-postprocessors:

==============
postProcessors
==============

Add postProcessors to the FORM.

postProcessors define how TYPO3 processes submitted forms after the form is
rendered according to filters and rules.

Multiple postProcessors are accepted for one FORM object, but you have to
add these postProcessors one by one.

Currently there are two postProcessors:

.. toctree::
    :maxdepth: 5
    :titlesonly:
    :glob:

    Mail/Index.rst
    Redirect/Index.rst

**Example:**

.. code-block:: typoscript

  postProcessor {
    1 = mail
    1 {
      recipientEmail = bar@foo.org
      senderEmail = foo@bar.com
      subject = Baz
    }
    2 = redirect
    2 {
      destination = 5
    }
  }

The processing will be done in order of the postProcessors.

