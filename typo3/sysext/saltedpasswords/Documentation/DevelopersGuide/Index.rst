.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _developer-s-guide:

Developer's Guide
-----------------

The Salted user password hashes extension is written in an OOP style
and thus makes it very easy to extend or use it in your TYPO3
extension.


.. _creating-a-hash:

Creating a hash
^^^^^^^^^^^^^^^

When you want to create a new salted user password hash from a given
plain-text password, these are the steps to be done:

* let the factory deliver an instance of the default hashing class with given context `FE` or `BE`
* create the salted user password hash

Example implementation for TYPO3 frontend:

::

   // Given plain text password
   $password = 'XXX';

   $hashInstance = GeneralUtility::makeInstance(SaltFactory::class)->getDefaultHashInstance('FE');
   $hashedPassword = $hashInstance->getHashedPassword($password);


.. _checking-a-password:

Checking a password
^^^^^^^^^^^^^^^^^^^

When you want to check a plain-text password against a salted user
password hash, these are the steps to be done:

* let the factory deliver an instance of the according hashing class
* compare plain-text password with salted user password hash

Example implementation for TYPO3 frontend:

::

   // plain-text password
   $password = 'XXX';
   // stored password hash
   $passwordHash = 'YYY';
   $success = GeneralUtility::makeInstance(SaltFactory::class)
       ->get($saltedPassword)
       ->checkPassword($password, $passwordHash);


.. _adding-a-new-salting-method:

Adding a new salting method
^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you decide to add an additional salting method, you can easily make
such additional method available for this extension.

Steps to be done:

- create a new salting class that implements interface
  :code:`\TYPO3\CMS\Saltedpasswords\Salt\SaltInterface`

  Optional: take advantage of abstract class
  :code:`\TYPO3\CMS\Saltedpasswords\Salt\AbstractComposedSalt` (see class
  :code:`\TYPO3\CMS\Saltedpasswords\Salt\Md5Salt` for an example implementation)

- register your salting method class
  (:code:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods']`)
  to make it available for the salt factory (see :file:`Classes\Salt\SaltFactory.php` for an example)

