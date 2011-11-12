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
	
	import flash.display.StageDisplayState;	
	import flash.display.DisplayObject;
	import flash.display.Stage;
	import flash.events.EventDispatcher;
	import flash.geom.Rectangle;
	
	import org.flowplayer.layout.Layout;	

	internal class AbstractConstraint extends EventDispatcher {

		private var layout:Layout;
		private var margins:Array;		
		private var view:DisplayObject;

		public function AbstractConstraint(view:DisplayObject, layout:Layout, margins:Array) {
			this.layout = layout;
			this.view = view;
			this.margins = margins;
			if (! this.margins) {
				this.margins = new Array();
			}
		}

		public function setMarginConstraint(margin:Number, constraint:Constraint):void {
			margins[margin] = constraint;
		}
		
		public function removeMarginConstraint(constraint:Constraint):void {
			for (var i : Number = 0; i < margins.length; i++) {
				if (margins[i] == constraint)
					margins[i] = null;
			}
		} 

		public function getConstrainedView():DisplayObject {
			return view;
		}

		public function getMarginConstraints():Array {
			return margins;
		}

		protected function getContainer():DisplayObject {
			return layout.getContainer();
		}
		
		protected function getContainerWidth():Number {
			return getContainer() is Stage ? Arrange.getStageWidth(getContainer() as Stage) : getContainer().width;
		}
		
		protected function getContainerHeight():Number {
			return getContainer() is Stage ? Arrange.getStageHeight(getContainer() as Stage) : getContainer().height;
		}
	}
}
