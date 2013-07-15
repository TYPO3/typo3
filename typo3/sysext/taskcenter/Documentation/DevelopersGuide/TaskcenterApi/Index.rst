.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _taskcenter-api:

Taskcenter API
^^^^^^^^^^^^^^

It is possible to refer to the Taskcenter from other extensions. Once
a :code:`\TYPO3\CMS\Taskcenter\Controller\TaskModuleController` object
has been instantiated all of its public methods can be used.
The PHPdoc of the methods should be
enough to understand what each is to be used for. It would be
excessive to describe them all here.

However a few deserve a special mention:

- :code:`description()`: This method is used to render a description
  including title and description.

- :code:`renderListMenu()`: This method is used to render a menu of sub
  items by a given array holding the following information: Title, Link,
  Path to an icon, description and a special description which is not
  using :code:`htmlspecialchars()`.

