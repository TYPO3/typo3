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
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.geom.Rectangle;
	import flash.system.Capabilities;
	import flash.utils.Dictionary;
	
	import org.flowplayer.layout.DrawWrapper;
	import org.flowplayer.layout.Layout;
	import org.flowplayer.layout.MarginLayout;
    import org.flowplayer.model.DisplayProperties;
    import org.flowplayer.model.DisplayProperties;
	import org.flowplayer.model.DisplayPropertiesImpl;
    import org.flowplayer.util.Assert;
    import org.flowplayer.util.Log;

	/**
	 * @author anssi
	 */
	internal class Panel extends Sprite {

		private var log:Log = new Log(this);
		private var layout:Layout;
		private var childProps:Array;

		public function Panel() {
			addEventListener(Event.ADDED_TO_STAGE, createLayout);
			childProps = new Array();
		}

		public function addView(view:DisplayObject, resizeListener:Object = null, properties:DisplayProperties = null):void {
			if (!properties) {
				properties = new DisplayPropertiesImpl();
				properties.left = 0;
				properties.top = 0;
				properties.width = view.width || "50%";
				properties.height = view.height || "50%";
			} else {
				if (! properties.dimensions.height.hasValue()) {
					properties.height = view.height;
				}
				if (! properties.dimensions.width.hasValue()) {
					properties.width = view.width;
				}
				if (! (properties.position.left.hasValue() || properties.position.right.hasValue())) {
					properties.left = "50%";
				}
				if (! (properties.position.top.hasValue() || properties.position.bottom.hasValue())) {
					properties.top = "50%";
				}
			}
			if (properties.zIndex < 0) {
				properties.zIndex = 1;
			}
			var listener:Function;
			if (resizeListener)
				 listener = resizeListener is Function ? resizeListener as Function : view[resizeListener];
			else
				listener = new DrawWrapper(view).draw;
			view.alpha = properties.alpha;
			
			properties.setDisplayObject(view);
			addChildView(properties);
			
			layout.addView(view, listener, properties);
		}

		override public function swapChildren(child1:DisplayObject, child2:DisplayObject):void {
			log.warn("swapChildren on Panel called, overridden here and does nothing");
		}
		
		private function addChildView(properties:DisplayProperties):void {
			log.info("addChildView() updating Z index of " + properties + ", target Z index is " + properties.zIndex + ", numChildren " + numChildren);
            Assert.notNull(properties.getDisplayObject(), "displayObject cannot be null");
			for (var i:int = 0; i < numChildren; i++)
				log.debug("addChildView(), " + getChildAt(i) + " at " + i);

			var index:Number;
			if (numChildren > 0 && childProps.length > 0 && properties.zIndex <= childProps[childProps.length -1].zIndex) {
				index = getPositionToAddByZIndex(properties.zIndex);
                log.debug("addChildView() adding child at " + index);
                try {
                    addChildAt(properties.getDisplayObject(), index);
                } catch (e:Error) {
                    log.info("addChildView(), error " + e);
                    // a workaraound to some strange bugs with invalid index
                    addChild(properties.getDisplayObject());
                }
			} else {
				index = numChildren;
                log.debug("addChildView() adding to top " + properties.getDisplayObject());
				addChild(properties.getDisplayObject());
			}
			
			if(childProps.length == 0)
				childProps.push(properties);
			else
				childProps.splice(index, 0, properties);

			log.debug("addChildView() child indexes are now: ");

			for (var j:int = 0; j < numChildren; j++)
				log.debug("addChildView(), " + getChildAt(j) + " at " + j);
		}
		
		private function getPositionToAddByZIndex(zIndex:int):int {
			
			for(var i:int = 0; i < childProps.length; ++i)
				if(childProps[i].zIndex >= zIndex) { 
					return i;
					break;
				}
			return childProps.length - 1;
		}

		public function getZIndex(view:DisplayObject):int {
			try {
				return getChildIndex(view);
			} catch (e:Error) {
				// view not added in this panel
			}
			return -1;
		}

		public function update(view:DisplayObject, properties:DisplayProperties):Rectangle {
			return layout.update(view, properties);
		}

		private function removeView(view:DisplayObject):void {
			log.debug("removeView " + view);
			if (! getChildByName(view.name)) {
				return;
			}
            for(var i:int = 0; i < childProps.length; ++i) {
                var props:DisplayProperties = childProps[i];
                if(props.getDisplayObject() == view) {
                    childProps.splice(i, 1);
                    break;
                }
            }

			super.removeChild(view);
			layout.removeView(view);
		}
		
		public override function removeChild(child:DisplayObject):DisplayObject {
			removeView(child);
			return child;
		}

		private function createLayout(event:Event):void {
			layout = new MarginLayout(stage);
		}
		
		/**
		 * Redraw the panel.
		 * @param disp if specified only this display object is redrawn
		 */
		public function draw(disp:DisplayObject = null):void {
			layout.draw(disp);
		}
	}
}
