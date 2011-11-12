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
	import org.flowplayer.layout.Constraint;	
	
	import flash.display.DisplayObject;
	import flash.geom.Rectangle;
	
	import org.flowplayer.layout.AbstractConstraint;
	import org.flowplayer.layout.Layout;	

	/**
	 * @author anssi
	 */
	internal class MarginConstraint extends AbstractConstraint implements Constraint {
		private var log:Log = new Log(this);
		// TODO: percentage dimensions
		private var _dimensions:Dimensions;

		public function MarginConstraint(view:DisplayObject, layout:Layout, margins:Array, dimensions:Dimensions) {
			super(view, layout, margins);
			_dimensions = dimensions;
		}
		
		public function getBounds():Rectangle {
			return new Rectangle(getLeftMargin(), getTopMargin(), getWidth(), getHeight());
		}
		
		private function getWidth():Number {
			return _dimensions.width.toPx(getContainerWidth()) || getContainerWidth() - getLeftMargin() - getRightMargin();
		}
		
		private function getHeight():Number {
			return _dimensions.height.toPx(getContainerHeight()) || getContainerHeight() - getTopMargin() - getBottomMargin();
		}
		
		protected function getTopMargin():Number {
			return getMargin(0, 2, "height", getContainerHeight());
		}

		protected function getRightMargin():Number {
			return getMargin(1, 3, "width", getContainerWidth());
		}
		
		protected function getBottomMargin():Number {
			return getMargin(2, 0, "height", getContainerHeight());
		}
						
		protected function getLeftMargin():Number {
			return getMargin(3, 1, "width", getContainerWidth());
		}

		private function getMargin(margin:Number, otherMargin:Number, dimensionProp:String, containerLength:Number):Number {
			log.debug(getConstrainedView() + ", getMargin() " + margin);
			var constraint:Constraint = getMarginConstraints()[margin];
			if (! constraint) {
				// if we have the opposite constraint, that will rule now
				var oppositeConstraint:Constraint = getMarginConstraints()[otherMargin];
				
				var length:Number = _dimensions[dimensionProp].toPx(containerLength);
				if (!oppositeConstraint)
					throw new Error(getConstrainedView() + ": not enough info to place object on Panel. Need top|bottom and left|right display properties.");
				
				
				var result:Number = oppositeConstraint ? containerLength - length - oppositeConstraint.getBounds()[dimensionProp] : 0;
//				log.debug(getConstrainedView() + ": " + dimensionProp + ": " + length  + ": getMargin(), margin " +margin+ " using opposite constraint " + otherMargin + " is " + result);
				return result;
			} else {
				log.debug(getConstrainedView() + ": getMargin(), constraint at margin " + margin + ": " + constraint + ", returns value " + constraint.getBounds()[dimensionProp]);
				return constraint.getBounds()[dimensionProp];
			}
		}
	}
}
