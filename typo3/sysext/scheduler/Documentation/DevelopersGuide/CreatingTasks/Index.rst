.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _creating-tasks:

Creating a new task
^^^^^^^^^^^^^^^^^^^


.. _creating-task-class:

Creating the task class
"""""""""""""""""""""""

This is the heart of a task. It is the code that actually does what
the task is supposed to do. A task is represented by a PHP class that
extends the base task class:

::

	namespace Vendor\Extension\Task;
	class MyTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {
		public function execute() {
			...
		}
	}

The only method that  **must** be implemented is the :code:`execute()`
method which is expected to perform the task logic. The method must
return a boolean value of "true" if the execution was successful, and
"false" otherwise. It may also throw exceptions to report more
precisely on errors that may happen during the run. The message of the
exception is stored in the database so that it can be displayed in the
BE module.

A method called :code:`getAdditionalInformation()` may optionally be
implemented too. It is called by the BE module to display additional
information about a registered task in the list view. This is quite
convenient if a task class may be registered several times with
different parameters, in order to tell apart the various
registrations.

If the task class uses a constructor **it is absolutely necessary** to
include a call to the parent's constructor, most probably as the first
thing inside the constructor, unless there are some very special
reasons not to:

::

	namespace Vendor\Extension\Task;
	class MyTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {
		public function __construct() {
			parent::__construct();
			// Your code here...
		}
	}


.. _using-additional-fields:

Using additional fields
"""""""""""""""""""""""

A task class may require additional parameters to be executed
properly. For example, the "test" task is designed to send an e-mail,
but the e-mail address must be defined at the time when the task class
is actually registered. This appears as an additional field in the
task registration form. The Scheduler provides an interface which may
be implemented to provide such fields. It is comprised of three
methods, which must all be implemented (since it's an interface).

The implementation may be done in a separate class or in the same
class as the task. It may look something like:

::

	namespace Vendor\Extension\Task;
	class MyTaskAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {
		public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
			...
		}
		public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
			...
		}
		public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
			...
		}
	}

The three methods of the interface are described below:


.. _getadditionalfields:

getAdditionalFields
~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Method
         getAdditionalFields

   Purpose
         This method returns the list of fields to display in the editing form.

         This list of fields is a 2-dimensional array. The first dimension uses
         the ID of the field (as used in the "id" attribute of the field tag).
         Then for each field, there must be the HTML code to render the field,
         the label of the field, the key and the label for the context-
         sensitive help (CSH).

         All this is documented in the
         :code:`\TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface` interface and can be
         seen in action in the existing task classes.

   Parameters
         :code:`$taskInfo` : an array containing the information about the
         current task. May be modified inside this method to set default
         values, for example.

         :code:`$task` : the current task object (when editing; when adding,
         "null" is passed to the method).

         :code:`$parentObject` : a back-reference to the calling BE module's
         object.



.. _validateadditionalfields:

validateAdditionalFields
~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Method
         validateAdditionalFields

   Purpose
         This method is called to validate the values that were input in the
         additional fields provided by the specific task. It is expected to
         return false if any of the fields contained errors, true otherwise.

         The method should use the parent object's :code:`addMessage()` method
         to output messages about validation errors.

   Parameters
         :code:`$submittedData` : array of values from the submitted form. May
         be modified inside the method.

         :code:`$parentObject` : a back-reference to the calling BE module's
         object.



.. _saveadditionalfields:

saveAdditionalFields
~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Method
         saveAdditionalFields

   Purpose
         This method is used to store the values contained in the additional
         fields. The simplest method is to simply assign them to member
         variables, so that they will be stored along the serialized task
         object in the database (see :ref:`technical-background`).

   Parameters
         :code:`$submittedData` : array of values from the submitted form,
         after validation.

         :code:`$task` : the current task object.



.. _security-issues:

Security issues
"""""""""""""""

The handling of additional fields is entirely up to the developer, in
particular as far as validation and display is concerned. Thus it is
up to you to make sure that the data entered in your additional fields
does not contain any harmful input (for example, XSS).

The safest way to proceed is to very strictly handle input. For
example, if all you expect is some integer number, pass the value
through a typecast to (int). If it's a string, but you don't
expect any markup in it, run it through :code:`strip\_tags()` . Such
simple measures can filter most of the harmful code.

One more thing to mind. In the :code:`getAdditionalFields()` method,
the whole form element must be assembled. If the input didn't
validate, you may well want to insert it in the field again, so that
the user can correct it. Harmful code entered at that point may be
executed. It is thus very strongly recommended to use
:code:`htmlspecialchars()` in this case. Example:

::

   $fieldCode = '<input type="text" class="form-control" name="tx_scheduler[email]" id="' . $fieldID . '" value="' . htmlspecialchars($taskInfo['email']) . '" size="30">';


.. _naming-of-additional-fields:

Naming of additional fields
"""""""""""""""""""""""""""

The Scheduler expects all fields in the add/edit form to be named
:code:`tx\_scheduler[...]` . Values from fields that don't follow this
pattern will **not** appear in the :code:`$submittedData` array.

It is also important to think about using field names that will not
create conflicts with other existing fields or future fields that may
happen. Since there is no name-spacing mechanism available, it is up
to each developer to choose proper names. A very good practice is to
prepend the extension's key to the additional fields names in order to
guarantee the unicity of those names.


.. _bad-examples:

Bad examples
~~~~~~~~~~~~

Here are some examples of bad additional fields names:

::

   foo
   tx_scheduler[name]

The first name doesn't use the syntax and thus will not be handled by
the Scheduler. The second one respects that syntax, but the name is
too generic and may cause conflicts with other fields.


.. _good-examples:

Good examples
~~~~~~~~~~~~~

Here are good examples of additional fields names:

::

   tx_scheduler[myextension_myvalue]
   tx_scheduler[myextension][myvalue]

In both cases the proper syntax is used as well as the extension key,
making it very unlikely that a naming conflict could happen.


.. _declare-task-class:

Declaring the task class
""""""""""""""""""""""""

As a last step, the task class must be declared so the Scheduler knows
of its existence. The declaration must be placed in the
:code:`ext_localconf.php` file of the extension that provides the
task. Let's look at one of the base classes declaration as an example:

::

	// Add caching framework garbage collection task
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask::class] = array(
		'extension' => $_EXTKEY,
		'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:cachingFrameworkGarbageCollection.name',
		'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:cachingFrameworkGarbageCollection.description',
		'additionalFields' => \TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionAdditionalFieldProvider::class
	);

The registration is made in the array
:code:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']`.
The key used corresponds to the task class. Then come 4 parameters:

- **extension** : the key of the extension that provides the class. This
  is used for informational purposes.

- **title** : the name of the task. May use localized labels.

- **description** : a text describing the task. It is displayed in the
  information screen of the BE module. May use localized labels.

- **additionalFields** : the name of the class that provides the
  additional fields. Leave empty if task class does not require any such
  fields.


.. _serialized-objects:

Working with serialized objects
"""""""""""""""""""""""""""""""

When a task is registered with the Scheduler the corresponding object
instance is serialized and stored in the database (see Appendix A for
more details). This is not a very common practice. There are
advantages but also some pitfalls, so please read this section
carefully.

A serialized object may happen to be "out of sync" with its class if
the class changes some of its variables or methods. If a variable's
name is changed or if variables are added or removed, the serialized
object will not reflect these changes. The same goes if a method is
renamed, added or deleted. Problems will also arise if the number or
order of arguments used to call a method are changed. In such cases
weird errors may appear, which can be very difficult to track. The
only solution is to delete the registered task and register it anew.

To minimize such risks it is worth to consider implementing the
business logic in a separate class, so that the task class itself
changes as little as possible. The :code:`execute()` should be as
simple as possible. Consider the following:

::

	class MyTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {
		public function execute() {
			$businessLogic = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Vendor\Extension\BusinessLogic::class);
			$businessLogic->run(arg1, arg2, …);
		}
	}

In such a setup the :code:`execute()` is kept to the strict minimum
and the operations themselves are handled by a separate class.

Also remember that the constructor is **not** called when
unserializing an object. If some operations need to be run upon
unserialization, implement a :code:`__wakeup()` method instead.


.. _save-task-state:

Saving a task's state
"""""""""""""""""""""

The task's state is saved automatically at the **start** of its
execution. If you need to save a task's state at some point **during**
its execution, you can simply call the task's own :code:`save()`
method.

