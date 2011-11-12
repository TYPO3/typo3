/*    
 *    Copyright (c) 2008-2011 Flowplayer Oy *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Flowplayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Flowplayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.view {
	import flash.display.DisplayObject;
	
	import org.flowplayer.util.Log;
	import org.goasap.items.LinearGo;		
	/**
	 * @author api
	 */
	public class Animation extends LinearGo {
		
		protected var log:Log = new Log(this);
		private var _target:DisplayObject;
		private var _targetValue:Number;
		private var _startValue:Number;
		private var _tweenProperty:String;
		private var _canceled:Boolean;

		public function Animation(target:DisplayObject, tweenProperty:String, targetValue:Number, durationMillis:Number = 500) {
			super(0, durationMillis/1000);
			_target = target;
			_targetValue = targetValue;
			_tweenProperty = tweenProperty;
			useRounding = true;
		}
		
		public function cancel():Boolean {
			_canceled = true;
			return stop();
		}

		protected function startFrom(value:Number):Boolean {
			log.debug("starting with start value " + value);
			_startValue = value;
			_target[_tweenProperty] = value;
			_change = _targetValue - _startValue;
			return super.start();
		}

		override public function start():Boolean {
			_startValue = _target[_tweenProperty];
			log.debug("starting with start value " + _startValue);
			_change = _targetValue - _startValue;
			return super.start();
		}

		override protected function onUpdate(type:String):void {
			// Basic tween implementation using the formula Value=Start+(Change*Position).
			// Position is a 0-1 multiplier run by LinearGo.
			var newValue:Number = _startValue + (_targetValue - _startValue) * _position;
			_target[_tweenProperty] = _tweenProperty == "alpha" ? newValue : correctValue(newValue);
			
			if (_target[_tweenProperty] == _targetValue) {
				log.debug("completed for target "+ target + ", property " + _tweenProperty + ", target value was " + _targetValue);
			}
		}
		
		public override function toString():String {
			return "[Animation] of property '" + _tweenProperty + "', start " + _startValue + ", target " + _targetValue;
		}
		
		protected function get target():DisplayObject {
			return _target;
		}
		
		public function get canceled():Boolean {
			return _canceled;
		}
		
		public function get tweenProperty():String {
			return _tweenProperty;
		}
	}
}
