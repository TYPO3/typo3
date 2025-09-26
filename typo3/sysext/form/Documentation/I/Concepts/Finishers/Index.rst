:navigation-title: Finishers

..  include:: /Includes.rst.txt
..  _concepts-finishers:

============================================
Finishers: post-submission actions for forms
============================================

Once a form is submitted successfully in TYPO3, finishers decide what happens
next — like sending an email, redirecting to another page, or showing a
confirmation. This page gives you a quick tour of the built-in finishers.
For full details, check out the :ref:`Finisher Options <apireference-finisheroptions>`.

There is also a dedicated chapter on
:ref:`translations of finisher options <concepts-frontendrendering-translation-finishers>`.

..  toctree::
    :maxdepth: 2
    :titlesonly:

    ReadyToUseFinishers/Index
    CustomFinisherImplementations/Index

..  _concepts-finishers-execution-order:

Finisher execution order
========================

..  important::
    Finishers are executed in the order defined in your form definition. The
    `Redirect finisher <https://docs.typo3.org/permalink/typo3/cms-form:concepts-finishers-redirectfinisher>`_
    stops the execution of all finishers.

This is especially important when you are using the redirect finisher. Make sure this
finisher is the very last one to be executed. The redirect finisher stops the
execution of all subsequent finishers in order to perform the redirect. In
other words, finishers defined after the redirect finisher will never be
executed.

If you are using the `Redirect finisher <https://docs.typo3.org/permalink/typo3/cms-form:concepts-finishers-redirectfinisher>`_
it **should** be defined last. All finisher coming after it will be ignored.

..  literalinclude:: ReadyToUseFinishers/RedirectFinisher/_codesnippets/_example-redirect.yaml
