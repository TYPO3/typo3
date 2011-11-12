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
	import flash.geom.Rectangle;
	
	import org.flowplayer.layout.AbstractLayout;
	import org.flowplayer.layout.Constraint;
	import org.flowplayer.layout.Layout;
	import org.flowplayer.util.Log;	

	/**
	 * @author anssi
	 */
	public class MarginLayout extends AbstractLayout implements Layout {

		private var log:Log = new Log(this);

		public function MarginLayout(container:DisplayObjectContainer) {
			super(container);
		}

		public override function addView(view:DisplayObject, listener:Function, properties:DisplayProperties):void {
			log.debug("addView, name " + properties.name + ", position " + properties.position);
			var constraint:MarginConstraint = new MarginConstraint(view, this, null, properties.dimensions);
			initConstraint(view, constraint, properties);
			addConstraint(constraint, listener);
//			log.info("added view " +view+ " to panel " + constraint.getBounds());
			draw(view);
		}
		
		public override function update(view:DisplayObject, properties:DisplayProperties):Rectangle {
//			log.debug("update, margins " + margins);
			var constraint:MarginConstraint = new MarginConstraint(view, this, null, properties.dimensions);
			initConstraint(view, constraint, properties);
			addConstraint(constraint);
//			log.info("updated view " +view+ " to position " + constraint.getBounds());
			return constraint.getBounds();
		}

		private function initConstraint(view:DisplayObject, constraint:MarginConstraint, properties:DisplayProperties):void {
			if (properties.position) {
				for (var i : Number = 0; i < 4; i++) {
					var margin:Constraint = getMarginConstraint(view, i, properties);
					if (margin)
						constraint.setMarginConstraint(i, margin);
				}
			}
		}
		
		private function getMarginConstraint(view:DisplayObject, i:Number, properties:DisplayProperties):Constraint {
			var position:Position = properties.position;
			if (i == 0) {
				if (position.top.pct >= 0) return new RelativeConstraint(view, properties.dimensions.height, getContainer(), position.top.pct, "height");
				if (position.top.px >= 0) return new FixedContraint(position.top.px);
			}
			if (i == 1) {
				if (position.right.pct >= 0) return new RelativeConstraint(view, properties.dimensions.width, getContainer(), position.right.pct, "width");
				if (position.right.px >= 0) return new FixedContraint(position.right.px);
			}
			if (i == 2) {
				if (position.bottom.pct >= 0) return new RelativeConstraint(view, properties.dimensions.height, getContainer(), position.bottom.pct, "height");
				if (position.bottom.px >= 0) return new FixedContraint(position.bottom.px);
			}
			if (i == 3) {
				if (position.left.pct >= 0) return new RelativeConstraint(view, properties.dimensions.width, getContainer(), position.left.pct, "width");
				if (position.left.px >= 0) return new FixedContraint(position.left.px);
			}
			return null;
		}
	}
}
