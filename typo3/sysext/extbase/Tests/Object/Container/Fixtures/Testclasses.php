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
class t3lib_object_tests_amixed_array implements t3lib_Singleton {
	public function __construct(t3lib_object_tests_b $b, t3lib_object_tests_c $c, array $myvalue=array()) {

	}
}
/**
 * test class A that depends on B and C and has a third default parameter in constructor
 *
 */
class t3lib_object_tests_amixed_string implements t3lib_Singleton {
	public function __construct(t3lib_object_tests_b $b, t3lib_object_tests_c $c, $myvalue='test') {

	}
}
/**
 * test class B that depends on C
 *
 */
class t3lib_object_tests_b implements t3lib_Singleton {
	public function __construct(t3lib_object_tests_c $c) {

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

interface t3lib_object_tests_someinterface {

}

/**
 * class which implements a Interface
 *
 */
class t3lib_object_tests_someimplementation implements t3lib_object_tests_someinterface, t3lib_Singleton {
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

	}
}

/**
 * classes that depends on each other (death look)
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
	public $o;
	public function __construct(t3lib_object_tests_resolveablecyclic2 $cyclic2) {
		$this->o = $cyclic2;
	}
}

/**
 *
 *
 */
class t3lib_object_tests_resolveablecyclic2 implements t3lib_Singleton {
	public $o;
	public function injectCyclic1(t3lib_object_tests_resolveablecyclic1 $o) {
		$this->o = $o;
	}
}


