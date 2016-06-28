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

The processing will be done in the order of the postProcessors.

Custom postProcessors
=====================

It is also possible to configure a custom class as a postProcessor. Just use
the class name as the postProcessor name.
The postProcessor class should implement `TYPO3\CMS\Form\PostProcess\PostProcessorInterface`

The custom postProcessor is not available within the form wizard. Currently,
there is no possibility to extend the wizard.

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

    3 = Vendor\ExtensionName\Folder\ClassName
    3 {
    }
  }

