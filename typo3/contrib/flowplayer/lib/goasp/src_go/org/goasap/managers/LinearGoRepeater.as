/**
 * Copyright (c) 2008 Moses Gunesch
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
package org.goasap.managers {

	/**
	 * An iterator used by LinearGo instances to track repeat play.
	 * @see org.goasap.items.LinearGo LinearGo
	 */
	public class LinearGoRepeater extends Repeater {
		
		/**
		 * Whether tween direction should reverse every other cycle.
		 */
		public function get reverseOnCycle() : Boolean {
			return _reverseOnCycle;
		}
		public function set reverseOnCycle(value : Boolean):void {
			if (unlocked())
				_reverseOnCycle = value;
		}
		
		/**
		 * Current play direction depending on reverseOnCycle and currentCycle.
		 * @return 1 for forward, -1 for reverse.
		 */
		public function get direction() : int {
			if (_reverseOnCycle && _currentCycle%2==1) {
				return -1;
			}
			return 1;
		}
		
		/**
		 * Storage for optional secondary easing to use on reverse cycles.
		 */
		public function get easingOnCycle() : Function {
			return _easingOnCycle;
		}
		public function set easingOnCycle(value : Function):void {
			if (unlocked())
				_easingOnCycle = value;
		}
		
		/**
		 * Additional parameters to use with easingOnCycle if the function accepts more than four.
		 */
		public function get extraEasingParams() : Array {
			return _extraEasingParams;
		}
		public function set extraEasingParams(value : Array):void {
			if (unlocked())
				_extraEasingParams = value;
		}
		
		/**
		 * @private 
		 * For use by LinearGo, simple way to see if easingOnCycle should be used in the current cycle.
		 */
		public function get currentCycleHasEasing() : Boolean {
			return (_reverseOnCycle && _currentCycle%2==1 && _easingOnCycle!=null);
		}
				
		/** @private */
		protected var _reverseOnCycle: Boolean = false;
				
		/** @private */
		protected var _easingOnCycle: Function;
				
		/** @private */
		protected var _extraEasingParams: Array;
		
		/**
		 * @param cycles			Number of times to play the LinearGo tween.
		 * @param reverseOnCycle	Whether tween direction should reverse every other cycle.
		 * @param easingOnCycle		Storage for optional secondary easing to use on reverse cycles.
		 * @param extraEasingParams	Additional parameters to use with easingOnCycle if the function accepts more than four.
		 */
		public function LinearGoRepeater(cycles: uint=1, reverseOnCycle:Boolean=true, easingOnCycle: Function=null, extraEasingParams: Array=null) {
			super(cycles);
			_reverseOnCycle = reverseOnCycle;
			_easingOnCycle = easingOnCycle;
			_extraEasingParams = extraEasingParams;
		}
	}
}
