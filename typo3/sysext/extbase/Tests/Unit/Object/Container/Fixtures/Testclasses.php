<?php

/**
 * a  singleton class
 *
 */
class t3lib_object_tests_singleton implements t3lib_Singleton {

}

/**
 * test class A that depends on B and C
 *
 */
class t3lib_object_tests_a {
	public $b;
	public $c;

	public function __construct( t3lib_object_tests_c $c, t3lib_object_tests_b $b) {
		$this->b = $b;
		$this->c = $c;
	}
}
/**
 * test class A that depends on B and C and has a third default parameter in constructor
 *
 */
class t3lib_object_tests_amixed_array {
	public $b;
	public $c;
	public $myvalue;
	public function __construct(t3lib_object_tests_b $b, t3lib_object_tests_c $c, array $myvalue=array('some' => 'default')) {
		$this->b = $b;
		$this->c = $c;
		$this->myvalue = $myvalue;
	}
}

/**
 * test class A that depends on B and C and has a third default parameter in constructor
 *
 */
class t3lib_object_tests_amixed_array_singleton implements t3lib_Singleton {
	public $b;
	public $c;
	public $myvalue;
	public function __construct(t3lib_object_tests_b $b, t3lib_object_tests_c $c, $someDefaultParameter = array('some' => 'default')) {
		$this->b = $b;
		$this->c = $c;
		$this->myvalue = $someDefaultParameter;
	}
}

/**
 * test class B that depends on C
 *
 */
class t3lib_object_tests_b implements t3lib_Singleton {
	public $c;
	public function __construct(t3lib_object_tests_c $c) {
		$this->c = $c;
	}
}


/**
 * test class C without dependencys
 *
 */
class t3lib_object_tests_c implements t3lib_Singleton {

}

/**
 * test class B-Child that extends Class B (therfore depends also on Class C)
 *
 */
class t3lib_object_tests_b_child extends t3lib_object_tests_b {
}

interface t3lib_object_tests_someinterface extends t3lib_Singleton {

}

/**
 * class which implements a Interface
 *
 */
class t3lib_object_tests_someimplementation implements t3lib_object_tests_someinterface {
}

/**
 * test class B-Child that extends Class B (therfore depends also on Class C)
 *
 */
class t3lib_object_tests_b_child_someimplementation extends t3lib_object_tests_b implements t3lib_object_tests_someinterface {
}

/**
 * class which depends on a Interface
 *
 */
class t3lib_object_tests_needsinterface {
	public function __construct(t3lib_object_tests_someinterface $i) {
		$this->dependency = $i;
	}
}

/**
 * Prototype classes that depend on each other
 *
 */
class t3lib_object_tests_cyclic1 {
	public function __construct(t3lib_object_tests_cyclic2 $c) {

	}
}

class t3lib_object_tests_cyclic2 {
	public function __construct(t3lib_object_tests_cyclic1 $c) {

	}
}

class t3lib_object_tests_cyclic1WithSetterDependency {
	public function injectFoo(t3lib_object_tests_cyclic2WithSetterDependency $c) {

	}
}

class t3lib_object_tests_cyclic2WithSetterDependency {
	public function injectFoo(t3lib_object_tests_cyclic1WithSetterDependency $c) {

	}
}

/**
 * class which has setter injections defined
 *
 */
class t3lib_object_tests_injectmethods {
	public $b;
	public $bchild;

	public function injectClassB(t3lib_object_tests_b $o) {
		$this->b = $o;
	}

	/**
	 * @inject
	 * @param t3lib_object_tests_b $o
	 */
	public function setClassBChild(t3lib_object_tests_b_child $o) {
		$this->bchild = $o;
	}
}

/**
 * class which needs extenson settings injected
 *
 */
class t3lib_object_tests_injectsettings {
	public $settings;
	public function injectExtensionSettings(array $settings) {
		$this->settings = $settings;
	}
}

/**
 *
 *
 */
class t3lib_object_tests_resolveablecyclic1 implements t3lib_Singleton {
	public $o2;
	public function __construct(t3lib_object_tests_resolveablecyclic2 $cyclic2) {
		$this->o2 = $cyclic2;
	}
}

/**
 *
 *
 */
class t3lib_object_tests_resolveablecyclic2 implements t3lib_Singleton {
	public $o1;
	public $o3;
	public function injectCyclic1(t3lib_object_tests_resolveablecyclic1 $cyclic1) {
		$this->o1 = $cyclic1;
	}
	public function injectCyclic3(t3lib_object_tests_resolveablecyclic3 $cyclic3) {
		$this->o3 = $cyclic3;
	}
}

/**
 *
 *
 */
class t3lib_object_tests_resolveablecyclic3 implements t3lib_Singleton {
	public $o1;
	public function injectCyclic1(t3lib_object_tests_resolveablecyclic1 $cyclic1) {
		$this->o1 = $cyclic1;
	}
}

class t3lib_object_tests_class_with_injectsettings {
	public function injectFoo(t3lib_object_tests_resolveablecyclic1 $c1) {
	}

	public function injectSettings(array $settings) {
	}
}

/*
 *  a Singleton requires a Prototype for Injection -> allowed, autowiring active, but in development context we write a log message, as it is bad practice and most likely points to some logic error.
If a Singleton requires a Singleton for Injection -> allowed, autowiring active
If a Prototype requires a Prototype for Injection -> allowed, autowiring active
If a Prototype requires a Singleton for Injection -> allowed, autowiring active
 */
class t3lib_object_singleton implements t3lib_Singleton {
}

class t3lib_object_prototype {
}

class t3lib_object_singletonNeedsPrototype implements t3lib_Singleton {
	public function injectDependency(t3lib_object_prototype $dependency) {
		$this->dependency = $dependency;
	}
}

class t3lib_object_singletonNeedsSingleton implements t3lib_Singleton {
	public function injectDependency(t3lib_object_singleton $dependency) {
		$this->dependency = $dependency;
	}
}
class t3lib_object_prototypeNeedsPrototype {
	public function injectDependency(t3lib_object_prototype $dependency) {
		$this->dependency = $dependency;
	}
}
class t3lib_object_prototypeNeedsSingleton {
	public function injectDependency(t3lib_object_singleton $dependency) {
		$this->dependency = $dependency;
	}
}

class t3lib_object_singletonNeedsPrototypeInConstructor implements t3lib_Singleton {
	public function __construct(t3lib_object_prototype $dependency) {
		$this->dependency = $dependency;
	}
}

class t3lib_object_singletonNeedsSingletonInConstructor implements t3lib_Singleton {
	public function __construct(t3lib_object_singleton $dependency) {
		$this->dependency = $dependency;
	}
}
class t3lib_object_prototypeNeedsPrototypeInConstructor {
	public function __construct(t3lib_object_prototype $dependency) {
		$this->dependency = $dependency;
	}
}
class t3lib_object_prototypeNeedsSingletonInConstructor {
	public function __construct(t3lib_object_singleton $dependency) {
		$this->dependency = $dependency;
	}
}
