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
	import flash.utils.getTimer;
	
	import org.goasap.GoEngine;
	import org.goasap.interfaces.IUpdatable;
	import org.goasap.items.GoItem;	

	/**
	 * A custom advance type that triggers after a specific duration has completed.
	 * 
	 * @author Moses Gunesch
	 */
	public class OnDurationComplete extends SequenceAdvance implements IUpdatable {
		
		// -== Public Properties ==-
		
		/**
		 * The duration after which advance should occur.
		 */
		public function get duration() : Number {
			return _duration;
		}
		
		/**
		 * The pulse used to monitor the duration. Defaults to GoItem.defaultPulseInterval 
		 * if not specified. 
		 * 
		 * <p>(Note that this system is more accurate than flash.utilss.Timer, especially for 
		 * pause/resume.)</p>
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
		protected var _duration : Number;
		/**
		 * @private
		 */
		protected var _tweenDuration : Number;
		/**
		 * @private
		 */
		protected var _pulse : Number;
		/**
		 * @private
		 */
		protected var _pauseTime : Number;
		/**
		 * @private
		 */
		protected var _startTime : int;
		
		// -== Public Methods ==-
		
		/**
		 * @param seconds	The duration after which advance should occur.
		 * @param pulseInterval	The pulse used to monitor the duration. Defaults to 
		 * 						GoItem.defaultPulseInterval if not specified.
		 */
		public function OnDurationComplete(seconds:Number, pulseInterval:Number=NaN) {
			super();
			_duration = (isNaN(seconds) ? 0 : Math.max(seconds, 0));
			_pulse = pulseInterval;
		}
		
		override public function start() : Boolean {
			_startTime = getTimer();
			_tweenDuration = (_duration * 1000 * Math.max(0, GoItem.timeMultiplier));
			_pauseTime = NaN;
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
			_pauseTime = getTimer();
			GoEngine.removeItem(this);
			return true;
		}
		
		override public function resume() : Boolean {
			if (_state != PAUSED)
				return false;
			_state = PLAYING;
			_startTime = (getTimer() - (_pauseTime - _startTime)); 
			GoEngine.addItem(this);
			return true;
		}
		
		override public function skipTo(seconds:Number) : Boolean { // untested, logic is copied from LinearGo.skipTo.
			if (_state==STOPPED)
				GoEngine.addItem(this);
			_pauseTime = NaN;
			_startTime = (getTimer() - (Math.min(seconds, _duration) * 1000 * Math.max(0, GoItem.timeMultiplier)));
			return true;
		}
		
		public function update(currentTime : Number) : void {
			if (currentTime >= _startTime + _tweenDuration) {
				super.dispatchAdvance();
			}
		}
	}
}
