..  include:: /Includes.rst.txt

..  _finishers:
..  _finishers-introduction:
..  _finishers-overview-of-finishers:

=========
Finishers
=========

To each form any number of so-called "finishers" can be added. Finishers allow
you to specify actions that will be executed once the form has been submitted by
the user.

In the following chapter, each finisher and its functions are explained. Not all
of the existing finishers can be added via the form editor. There are additional
finishers that can only be added by integrators/ administrators. The following
finishers are available by default:

*   :ref:`Email to sender (form submitter) <finishers-email-to-sender>`
*   :ref:`Email to receiver (you) <finishers-email-to-receiver>`
*   :ref:`Redirect to a page <finishers-redirect>`
*   :ref:`Delete uploads <finishers-delete-uploads>`
*   :ref:`Confirmation message <finishers-confirmation-message>`

..  figure:: Images/form_finishers_overview.png
    :alt: Form editor - add new finishers.

    Form editor - add new finishers

..  important::

    Finishers are executed in the order defined in your form definition. This is
    especially important when you are using the ``Redirect finisher``. Make sure
    this finisher is the very last one to be executed. The ``Redirect finisher``
    stops the execution of all subsequent finishers in order to perform the redirect.
    I.e. finishers defined after the ``Redirect finisher`` will not be executed in
    any case.
