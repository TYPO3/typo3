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
	import org.flowplayer.util.Arrange;	
	
	import flash.display.DisplayObject;
	import flash.display.Stage;
	import flash.geom.Rectangle;
	
	import org.flowplayer.layout.Constraint;
	import org.flowplayer.util.Log;		

	/**
	 * @author api
	 */
	internal class RelativeConstraint implements Constraint {

		private var log:Log = new Log(this);
		private var _view:DisplayObject;
		private var _reference:DisplayObject;
		private var _marginPercentage:Number;
		private var _viewProperty:String;
		private var _length:Length;

		public function RelativeConstraint(view:DisplayObject, length:Length, reference:DisplayObject, marginPercentage:Number, viewProperty:String) {
			_view = view;
			_length = length;
			_reference = reference;
			_marginPercentage = marginPercentage;
			_viewProperty = viewProperty;
		}

		public function getConstrainedView():DisplayObject {
			return null;
		}
		
		public function getBounds():Rectangle {
			var viewLength:Number = getViewLength();
			var length:Number = getReferenceLength() * _marginPercentage/100  - viewLength/2;
			return new Rectangle(0, 0, length, length);
		}
		
		private function getReferenceLength():Number {
			return _viewProperty == "width" ? Arrange.getWidth(_reference) : Arrange.getHeight(_reference);
		}

		private function getViewLength():Number {
			if (_length.pct >= 0) {
				var result:Number = getReferenceLength() * _length.pct / 100;
				log.debug("relative length " + _length.pct + "% out of " +getReferenceLength() + " is " + result);
				return result;
			}
			if (_length.px >= 0) return _length.px;
			return _view[_viewProperty];
		}

		public function getMarginConstraints():Array {
			return null;
		}
		
		public function setMarginConstraint(margin:Number, constraint:Constraint):void {
		}
		
		public function removeMarginConstraint(constraint:Constraint):void {
		}
	}
}
