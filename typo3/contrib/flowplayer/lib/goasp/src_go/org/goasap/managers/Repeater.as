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
	import org.goasap.PlayableBase;		

	/**
	 * An iterator that can be used by playable items to track repeat play.
	 * 
	 * This utility is used by SequenceBase to provide a looping option for 
	 * Sequence and SequenceCA. When creating your own Go utilities you can
	 * make use of Repeater, which provides a next() iterator and a skipTo helper.
	 */
	public class Repeater {
		
		/**
		 * Makes code more human-readable, like <code>new Repeater(Repeater.INFINITE);</code>
		 */
		public static const INFINITE: uint = 0;
		
		/**
		 * Number of times the Repeater will iterate, which can be set to 
		 * zero or Repeater.INFINITE for indefinite repeating.
		 */
		public function get cycles() : uint {
			return _cycles;
		}
		public function set cycles(value : uint):void {
			if (unlocked())
				_cycles = value;
		}

		/**
		 * Current cycle starting at 0, which will continue to increase 
		 * up to <code>cycles</code> or indefinitely if cycles is set to 
		 * Repeater.INFINITE (zero).
		 */
		public function get currentCycle():uint {
			return _currentCycle;
		}
		
		/**
		 * True if cycles is not infinite and currentCycle has reached cycles.
		 */
		public function get done():Boolean {
			return (_currentCycle==_cycles && _cycles!=INFINITE);
		}
		
		/** @private */
		protected var _item : PlayableBase;
		
		/** @private */
		protected var _cycles: uint;
		
		/** @private */
		protected var _currentCycle : uint = 0;
		
		public function Repeater(cycles: uint=1) {
			_cycles = cycles;
		}
		
		/**
		 * @private
		 * For one-time internal use by parent playable item.
		 * When writing playable items that include a repeater,
		 * call this method once during construction or when
		 * the repeater is generated. This allows the repeater
		 * to check the parent item's state and reject calls
		 * to sensitive settings during your item's play.
		 * If you're subclassing Repeater, you can most simply
		 * query the method unlocked() to determine whether the
		 * parent item exists and is stopped.
		 */
		public function setParent(item:PlayableBase):void {
			if (!_item) _item = item;
		}
		
		/**
		 * @private
		 * For internal use by playable items.
		 * Iterates forward to final cycle, and returns false when done.
		 * You may also test this result in advance using hasNext().
		 * @return True if still active, false when done.
		 */
		public function next(): Boolean {
			if (_cycles==INFINITE) {
				_currentCycle++;
				return true;
			}
			
			if (_cycles-_currentCycle>0)
				_currentCycle++;
			
			if (_cycles==_currentCycle)
				return false;

			return true;
		}
		
		/**
		 * @private
		 * For internal use by playable items.
		 * @return False if cycles will be complete on next() call.
		 */
		public function hasNext(): Boolean {
			return (_cycles==INFINITE || _cycles-_currentCycle>1);
		}
		
		/**
		 * @private
		 * For internal use by playable items.
		 * Skips to a new currentCycle and aids playable items by calculating
		 * and returning the new play index.
		 * 
		 * @param fullUnit	The tween duration or sequence length
		 * @param amount	The skipTo amount requested which will be normalized
		 * 					to zero if negative, and if cycles are not set to infinite,
		 * 					capped to a maximum value of cycles * fullUnit.
		 * @return	The new play index
		 */
		public function skipTo(fullUnit:Number, amount:Number):Number {
			if (isNaN(fullUnit) || isNaN(amount))
				return 0; // fail on bad inputs
			amount = Math.max(0, amount);
			if (cycles!=INFINITE)
				amount = Math.min(amount, _cycles*fullUnit);
			_currentCycle = Math.floor(amount / fullUnit);
			return amount%fullUnit;
		}
		
		/**
		 * @private
		 * For internal use by playable items.
		 * 
		 * Resets current cycle to zero.
		 */
		public function reset(): void {
			_currentCycle = 0;
		}
		
		/** @private */
		protected function unlocked() : Boolean {
			return (!_item || (_item && _item.state==PlayableBase.STOPPED));
		}
	}
}
