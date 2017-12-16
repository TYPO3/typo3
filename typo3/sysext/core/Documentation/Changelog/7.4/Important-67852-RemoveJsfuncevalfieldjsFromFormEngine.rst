
.. include:: ../../Includes.txt

==============================================================
Important: #67852 - Remove jsfunc.evalfield.js from FormEngine
==============================================================

See :issue:`67852`

Description
===========

After 12 years, the usage of `jsfunc.evalfield.js` has been removed from `FormEngine`.
The JavaScript has been moved into FormEngineValidation AMD module.
Processor and Validator have been split up in two different functions.

Including the `jsfunc.evalfield.js` still works, but will be removed on short notice.
