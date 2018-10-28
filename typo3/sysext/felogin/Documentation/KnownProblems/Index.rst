.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _known-problems:

Known Problems
--------------

- if there is more than one felogin plugin on a page the password
  recovery option can cause problems. This is a general problem with
  pibase plugins, but in this case the cause is a small hash in the
  forgot password form which is stored in the frontend user session
  data. With multiple instances on a page only one of the hashes is
  stored and only one of the forgot password forms will work. Make sure
  there is only one felogin plugin on the page where the password
  recovery form is displayed.
- In TYPO3 6.2 the frontend form is not shown if you use
  css_styled_content version 4.5. In this case you must define the
  template in TypoScript constants:
  :code:`styles.content.loginform.templateFile = EXT:felogin/template.html`
