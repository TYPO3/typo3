/**
 * Copyright (c) 2007 Moses Gunesch
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
package org.goasap.utils.customadvance {
	import org.goasap.GoEngine;
	import org.goasap.interfaces.IUpdatable;
	import org.goasap.items.GoItem;

	/**
	 * A custom advance type that triggers when a callback returns true.
	 * 
	 * @author Moses Gunesch
	 */
	public class OnConditionTrue extends SequenceAdvance implements IUpdatable {
		
		// -== Public Properties ==-
		
		/**
		 * The pulse on which to call the callback function. Defaults to 
		 * GoItem.defaultPulseInterval if not specified.
		 */
		public function get pulseInterval() : int {
			if (isNaN(_pulse))
				_pulse = GoItem.defaultPulseInterval;
			return _pulse;
		}

		// -== Protected Properties ==-
		
		/**
		 * @private
		 */
		protected var _function : Function;
		/**
		 * @private
		 */
		protected var _pulse : Number;

		// -== Public Methods ==-
		
		/**
		 * @param callbackThatReturnsBoolean	Any function that returns a Boolean value
		 * @param pulseInterval					The pulse on which to call the callback function, which defaults to
		 * 										GoItem.defaultPulseInterval if not specified.
		 */
		public function OnConditionTrue(callbackThatReturnsBoolean: Function, pulseInterval:Number=NaN) : void {
			super();
			_function = callbackThatReturnsBoolean;
			_pulse = pulseInterval;
		}
		
		override public function start() : Boolean {
			GoEngine.addItem(this);
			_state = PLAYING;
			return true;
		}
		
		override public function stop() : Boolean {
			GoEngine.removeItem(this);
			_state = STOPPED;
			return true;
		}
		
		override public function pause() : Boolean {
			if (_state==STOPPED || _state==PAUSED)
				return false;
			_state = PAUSED;
			GoEngine.removeItem(this);
			return true;
		}
		
		override public function resume() : Boolean {
			if (_state != PAUSED)
				return false;
			GoEngine.addItem(this);
			return true;
		}
		
		public function update(currentTime : Number) : void {
			if (_function()===true)
				super.dispatchAdvance();
		}
	}
}
