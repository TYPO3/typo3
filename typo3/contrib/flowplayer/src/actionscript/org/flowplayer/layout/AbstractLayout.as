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
	import org.flowplayer.model.DisplayProperties;	
	
	import flash.display.DisplayObject;
	import flash.display.DisplayObjectContainer;
	import flash.display.Stage;
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.geom.Rectangle;
	import flash.utils.Dictionary;
	
	import org.flowplayer.util.Log;		

	/**
	 * @author anssi
	 */
	internal class AbstractLayout extends EventDispatcher implements Layout {

		private var log:Log = new Log(this);
		private var _container:DisplayObjectContainer;
		private var _constraints:Dictionary = new Dictionary();
		private var _listeners:Dictionary = new Dictionary();
		
		public function AbstractLayout(container:DisplayObjectContainer) {
			this._container = container;
			if (container is Stage)
				container.addEventListener(Event.RESIZE, onContainerResize);
		}
		
		private function onContainerResize(event:Event):void {
			draw();
		}
				
		public function draw(disp:DisplayObject = null):void {
			log.info("redrawing layout");
			if (disp) {
				var listenerFunc:Function = _listeners[disp];
				if (listenerFunc != null) {
					listenerFunc(new LayoutEvent(LayoutEvent.RESIZE, this));
				}
			} else {
				dispatchEvent(new LayoutEvent(LayoutEvent.RESIZE, this));
			}
		}
		

		public function addConstraint(constraint:Constraint, listenerFunc:Function = null):void {
			_constraints[constraint.getConstrainedView()] = constraint;
			if (listenerFunc != null) {
				_listeners[constraint.getConstrainedView()] = listenerFunc;
				this.addEventListener(LayoutEvent.RESIZE, listenerFunc);
			}
		}
		
		public function getConstraint(view:DisplayObject):Constraint {
			return _constraints[view];
		}
		
		public function removeView(view:DisplayObject):void {
			if (_listeners[view]) {
				this.removeEventListener(LayoutEvent.RESIZE, _listeners[view]);
			}
			delete _listeners[view];
			delete _constraints[view];
		}

		public function getContainer():DisplayObject {
			return _container;
		}
		
		public function getBounds(view:Object):Rectangle {
			var constraint:Constraint = _constraints[view];
			if (! constraint) return null;
			return constraint.getBounds();
		}
		
		protected function get constraints():Dictionary {
			return _constraints;
		}
		
		protected function get listeners():Dictionary {
			return _listeners;
		}
		
		public function addView(view:DisplayObject, listener:Function, properties:DisplayProperties):void {
		}
		
		public function update(view:DisplayObject, properties:DisplayProperties):Rectangle {
			return null;
		}
	}
}
