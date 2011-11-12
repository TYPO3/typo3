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

package org.flowplayer.layout {
	import org.flowplayer.util.Log;	
	import org.flowplayer.model.Cloneable;
	import org.flowplayer.util.NumberUtil;	

	/**
	 * @author api
	 */
	public class Length implements Cloneable {
		private var log:Log = new Log(this);
		private var _px:Number;
		private var _pct:Number;
		private var _clearPct:Boolean;

		public function Length(value:Object = null) {
            _px = NaN;
            _pct = NaN;
			if (value || (value is Number && Number(value) == 0)) {
				setValue(value);
			}
		}

		public function clone():Cloneable {
			var clone:Length = new Length();
			clone._pct = _pct;
			clone._px = _px;
			return clone;
		}
		
		public function set value(value:Object):void {
			setValue(value);
		}
		
		public function clear():void {
			_px = NaN;
			_pct = NaN;
		}
		
		public function setValue(valueObject:Object):void {
			if (valueObject && valueObject is String) {
				var valStr:String = valueObject as String;
				_pct = NumberUtil.decodePercentage(valStr);
				_px = NumberUtil.decodePixels(valStr);
			} else {
				_px = valueObject as Number;
				_pct = NaN;
			}
		}
		
		public function plus(other:Length, toPxFunc:Function, toPctFunc:Function):Length {
			log.debug(this + " plus() " + other);
			var result:Length = new Length();
			if (_px >= 0 && ! isNaN(other.px)) {
				result.px = _px + other.px;
			}
			if (_pct >= 0 && ! isNaN(other.pct)) {
				result.pct = _pct + other._pct;
			}
			if (_px >= 0 && ! isNaN(other.pct)) {
				result.px = toPxFunc(toPctFunc(_px) + other.pct);
			}
			if (_pct >= 0 && ! isNaN(other.px)) {
				result.pct = toPctFunc(toPxFunc(_pct) + other.px);
			}
			log.debug("plus(), result is " + result);
			return result;
		}

		public function hasValue():Boolean {
			return _px >= 0 || _pct>= 0;
		}

		public function get px():Number {
			return _px;
		}
		
		public function set px(px:Number):void {
			_px = px;
		}
		
		public function get pct():Number {
			return _pct;
		}
		
		public function set pct(pct:Number):void {
			_pct = pct;
		}
		
		public function asObject():Object {
			if (_px >= 0) return _px;
			if (_pct >= 0) return _pct + "%";
			return undefined;
		}
		
		public function toString():String {
			return "[Dimension] " + _px + "px -- " + _pct + "%";
		}
		
		public function toPx(containerLength:Number):Number {
			if (_pct >= 0) return containerLength * _pct / 100;
			if (_px >= 0) return _px;
			return undefined;
		}
	}
}
