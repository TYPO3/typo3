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
	import flash.display.DisplayObject;
	import flash.geom.Rectangle;
	
	import org.flowplayer.layout.Constraint;	

	/**
	 * @author anssi
	 */
	internal class FixedContraint implements Constraint {

		private var length:Number;

		public function FixedContraint(length:Number) {
			this.length = length;
		}
		
		public function getBounds():Rectangle {
			return new Rectangle(0, 0, length, length);
		}

		public function getConstrainedView():DisplayObject {
			return null;
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
