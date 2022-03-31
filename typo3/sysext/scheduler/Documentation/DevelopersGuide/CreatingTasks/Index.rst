.. include:: /Includes.rst.txt



.. _creating-tasks:

Creating a new task
^^^^^^^^^^^^^^^^^^^

The preferred method for creating a scheduler task is as a symfony command.
:ref:`Read about how to create and use symfony commands in TYPO3 here. <t3coreapi:symfony-console-commands>`


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

