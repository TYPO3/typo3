:navigation-title: Finishers

..  include:: /Includes.rst.txt
..  _concepts-finishers:

============================================
Finishers: post-submission actions for forms
============================================

When a form has been submitted in TYPO3, finishers decide what happens
next - sending an email, redirecting to another page, or showing a
confirmation message. This page gives you a quick tour of built-in finishers.
For more details, see :ref:`Finisher Options <apireference-finisheroptions>`.

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
    Finishers are executed in the order that is defined in your form definition. The
    `Redirect finisher <https://docs.typo3.org/permalink/typo3/cms-form:concepts-finishers-redirectfinisher>`_
    terminates all finishers.

If you are using the `redirect finisher <https://docs.typo3.org/permalink/typo3/cms-form:concepts-finishers-redirectfinisher>`_, make sure it is the last finisher
that is executed. The redirect finisher stops the
execution of all subsequent finishers in order to perform a redirect. Finishers
that are defined after a redirect finisher will be ignored.

..  literalinclude:: ReadyToUseFinishers/RedirectFinisher/_codesnippets/_example-redirect.yaml
