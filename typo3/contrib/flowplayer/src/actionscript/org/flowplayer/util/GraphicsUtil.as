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

package org.flowplayer.util {
	import flash.display.DisplayObject;	
	import flash.display.DisplayObjectContainer;
	import flash.display.GradientType;
	import flash.display.Graphics;
	import flash.display.*;
    import flash.geom.ColorTransform;
    import flash.geom.Matrix;

	/**
	 * @author api
	 */
	public class GraphicsUtil {
			
		public static function beginGradientFill(graphics:Graphics, width:Number, height:Number, color1:Number, color2:Number, alpha:Number = 1):void {
			var colors:Array = [color1, color2, color1];
			var matrix:Matrix = new Matrix();
			matrix.createGradientBox(width, height, Math.PI/2);
			
			graphics.beginGradientFill(GradientType.LINEAR, colors, 
				[alpha, alpha, alpha], [0, 127, 255], matrix);
		}
			
		public static function beginLinearGradientFill(graphics:Graphics, width:Number, height:Number, colors:Array, alphas:Array, x:int, y:int):void {
			var matrix:Matrix = new Matrix();
			matrix.createGradientBox(width, height, Math.PI/2, x, y);
			var ratios:Array = new Array();
			var gap:Number = 255/(colors.length-1)
			for (var i:Number = 0; i < colors.length; i++) {
				ratios.push(i*gap);
			}
 
			graphics.beginGradientFill(GradientType.LINEAR, colors, alphas, ratios, matrix);
		}
		
		public static function drawRoundRectangle(graphics:Graphics, x:Number, y:Number, width:Number, height:Number, borderRadius:Number):void {
			if (borderRadius > 0) {
				graphics.drawRoundRect(x, y, width, height, borderRadius, borderRadius);
			} else {
				graphics.drawRect(x, y, width, height);
			}
		}

		public static function addGradient(parent:DisplayObjectContainer, index:int, gradientAlphas:Array, borderRadius:Number, x:Number = 0, y:Number = 0, height:Number = 0):void {
			removeGradient(parent);
			var gradientHolder:Shape = new Shape();
			gradientHolder.name = "_gradient";
			parent.addChildAt(gradientHolder, index);
				
			gradientHolder.graphics.clear();

			beginFill(gradientHolder.graphics, gradientAlphas, parent.width, (height != 0 ? height : parent.height), x, y);
			GraphicsUtil.drawRoundRectangle(gradientHolder.graphics, x, y, parent.width, (height != 0 ? height : parent.height), borderRadius);
			gradientHolder.graphics.endFill();
		}
		
		public static function removeGradient(parent:DisplayObjectContainer):void {
			var gradientHolder:DisplayObject = parent.getChildByName("_gradient");
			if (gradientHolder) {
				parent.removeChild(gradientHolder);
			}
		}

		private static function beginFill(graph:Graphics, alphas:Array, width:Number, height:Number, x:int, y:int):void {
			var color:Array = new Array();
			for (var i:Number = 0; i < alphas.length; i++) {
				color.push(0xffffff);
			}
			beginLinearGradientFill(graph, width, height, color, alphas, x, y);
		}

        public static function transformColor(disp:DisplayObject, rgba:Array):void {
//            log.debug("transformColor, alphaOffset " + alphaOffset + ", RGB: " + redOffset + ", " + greenOffset + ", " + blueOffset);
            if (! disp) return;
            var transform:ColorTransform = new ColorTransform(0, 0, 0, rgba[3], rgba[0], rgba[1], rgba[2]);
            disp.transform.colorTransform = transform;
        }

	}
}
