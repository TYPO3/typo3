.. include:: ../Includes.txt

.. _administration:

Administration
--------------

Linkvalidator is a system extension.

If you are using Composer, you can install it like any other (core) extension
requiring the package `typo3/cms-linkvalidator`.

If you are not using Composer, you may have to activate Linkvalidator in
the Extension Manager.

Linkvalidator uses the HTTP request library shipped with TYPO3.
Please have a look in the :ref:`Global Configuration <t3coreapi:typo3ConfVars>`,
particularly at the HTTP settings.

There, you may define a default timeout. Generally, it is recommended
to always specify timeouts when working with Linkvalidator.


