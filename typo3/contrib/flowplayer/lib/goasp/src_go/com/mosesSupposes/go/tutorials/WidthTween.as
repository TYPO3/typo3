/**
 * Copyright (c) 2007 Moses Gunesch
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
package com.mosesSupposes.go.tutorials 
{
	import flash.display.DisplayObject;
	
	import org.goasap.items.LinearGo;

	/**
	 * A basic example of how you could build a tween on LinearGo.
	 * 
	 * @see SizeTweenMg SizeTweenMg: a similar example that works with OverlapMonitor
	 * 
	 * @author Moses Gunesch
	 */
	public class WidthTween extends LinearGo {
		
		// -== Public Properties ==-
		
		
		// In this example, the tween class has a width property, but the point of Go is that
		// the design is left up to you. If you prefer to parse an object or XML, or accept an
		// array of properties and targets, all of that is left up to you.
		public function get width() : Number {
			return _width;
		}
		public function set width(value : Number):void {
			if (_state==STOPPED)
				_width = value;
		} 		
		
		
		// See note above width getter, same applies here. Note that I've picked a specific datatype
		// for my tween target, but again it's wide open, including the variable name. The only thing
		// that tends to be universal is that you need at least one target and at least one property.
		public function get target() : DisplayObject {
			return _target;
		}
		public function set target(obj : DisplayObject):void {
			if (_state==STOPPED)
				_target = obj;
		}
		
		
		// -== Protected Properties ==-
		protected var _target : DisplayObject;
		protected var _width : Number;
		protected var _startWidth : Number;
		protected var _changeWidth : Number;
		
		
		// -== Public Methods ==-
		
		// You can design your own constructor for your tween classes of course!
		public function WidthTween(	target				: DisplayObject=null,
									widthTo				: Number=NaN,
									delay	 			: Number=NaN,
									duration 			: Number=NaN,
									easing 				: Function=null ) 
		{
			super(delay, duration, easing);
			_target = target;
			_width = widthTo;
		}
		
		
		// CONVENTION ALERT!
		
		// * Be aware that there are two standard conventions in Go that need to be 
		//   implemented manually by each LinearGo subclass, numbered below.
		
		
		override public function start():Boolean 
		{
			if (!_target || !_width || isNaN(_width))
				return false;
			
			_startWidth = _target.width; // Store start & change values for use in onUpdate.
			
			// Convention #1: useRelative   (*see note above)
			_changeWidth = (useRelative 
							? _width // relative positioning: like if the user set -10, we should change "by" that much.  
							: _width - _startWidth); // absolute positioning: the tween spans the difference from existing width.
			
			return (super.start());
		}
		
		
		// Convention #2:useRounding   (*see note above)
		// Always call correctValue() on tween values before setting them to targets. 
		// This fixes NaNs to 0 and applies Math.round based on the useRounding setting. 
		override protected function onUpdate(type:String) : void 
		{
			// Basic tween implementation using the formula Value=Start+(Change*Position).
			// Position is a 0-1 multiplier run by LinearGo.
			
			_target.width = super.correctValue( _startWidth + _changeWidth * _position );
		}
	}
}
