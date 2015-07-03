==============================================================
Important: #67852 - Remove jsfunc.evalfield.js from FormEngine
==============================================================

Description
===========

After 12 years, the usage of ``jsfunc.evalfield.js`` has been removed from ``FormEngine``.
The JavaScript has been moved into FormEngineValidation AMD module.
Processors and Validator has been split up in two different function.

Including the ``jsfunc.evalfield.js`` still works, but will be removed shortly.