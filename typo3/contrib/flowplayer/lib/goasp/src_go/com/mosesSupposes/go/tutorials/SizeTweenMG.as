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
	
	import org.goasap.interfaces.IManageable;
	import org.goasap.items.LinearGo;	

	/**
	 * This example handles both width & height tweens and is compatible 
	 * with OverlapMonitor. It also supports user-set start properties.
	 * For a more basic example see WidthTween.
	 * 
	 * <p>Setup: <code>GoEngine.addManager( new OverlapMonitor() );</code></p>
	 * 
	 * @see WidthTween
	 *
	 * @author Moses Gunesch
	 */
	public class SizeTweenMG extends LinearGo implements IManageable {
		
		// -== Public Properties ==-
		
		// See notes in WidthTween
		// Another strategy for multiple props is to define constants then
		// store props in an array. We'll keep it simple for this example.
		public function get width() : Number {
			return _width;
		}
		public function set width(value : Number):void {
			if (_state==STOPPED)
				_width = value;
		} 		
		
		public function get height() : Number {
			return _height;
		}
		public function set height(value : Number):void {
			if (_state==STOPPED)
				_height = value;
		} 		
		
		// Start settings are not a standard convention, just an option you can choose to provide if you want.
		public function get startWidth() : Number {
			return _startWidth;
		}
		public function set startWidth(value : Number):void {
			if (_state==STOPPED)
				_startWidth = value;
		} 		
		
		public function get startHeight() : Number {
			return _startHeight;
		}
		public function set startHeight(value : Number):void {
			if (_state==STOPPED)
				_startHeight = value;
		} 		
		
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
		protected var _height : Number;
		protected var _startWidth : Number;
		protected var _startHeight : Number;
		protected var _tweenStartWidth : Number; // used during the tween so that the user-set property isn't altered.
		protected var _tweenStartHeight : Number; // used during the tween so that the user-set property isn't altered.
		protected var _changeWidth : Number;
		protected var _changeHeight : Number;
		
		// -== Public Methods ==-
		
		// See notes in WidthTween
		public function SizeTweenMG(	target			: DisplayObject=null,
										widthTo			: Number=NaN,
										heightTo		: Number=NaN,
										delay	 		: Number=NaN,
										duration 		: Number=NaN,
										easing 			: Function=null ) 
		{
			super(delay, duration, easing);
			_target = target;
			_width = widthTo;
			_height = heightTo;
		}
		
		// See notes in WidthTween
		override public function start():Boolean 
		{
			if (!_target || (isNaN(_width) && isNaN(_height)))
				return false;
			
			_changeWidth = NaN;
			_changeHeight = NaN;
			if (!isNaN(_width)) {
				// Start settings are not a standard convention, just an option you can choose to provide if you want.
				if (isNaN(_startWidth))
					_tweenStartWidth = _target.width;
				else
					_target.width = _tweenStartWidth = _startWidth;
				
				// The useRelative property is a standard Go convention that each subclass must implement manually.	
				_changeWidth = (useRelative ? _width : _width - _tweenStartWidth);
			}
			
			if (!isNaN(_height)) {
				if (isNaN(_startHeight))
					_tweenStartHeight = _target.height;
				else
					_target.height = _tweenStartHeight = _startHeight;
				
				_changeHeight = (useRelative ? _height : _height - _tweenStartHeight);
			}
			return (super.start());
		}
		
		// See notes in WidthTween
		override protected function onUpdate(type:String) : void {
			// The useRounding property is a standard Go convention that can be implemented by calling correctValue() .	
			if (!isNaN(_changeWidth))
				_target.width = super.correctValue(_tweenStartWidth + _changeWidth * _position);
			
			if (!isNaN(_changeHeight))
				_target.height = super.correctValue(_tweenStartHeight + _changeHeight * _position);
				
		}
		
		// -== IManageable Implementation ==-
		
		// The following methods make the tween class compatible with OverlapMonitor 
		// or other managers. Please open the docs for the IManageable interface as you
		// review these 4 methods, so you get a clear picture of how the system works.
		
		// All animation targets currently being handled.
		public function getActiveTargets() : Array {
			return [ _target ];
		}
		
		// All property-strings currently being handled.
		public function getActiveProperties() : Array {
			var a:Array = new Array();
			if (!isNaN(_changeWidth))
				a.push("width");
			if (!isNaN(_changeHeight))
				a.push("height");
			return a;
		}
		
		// This method is the only complex one of the four. The general idea is to determine if there's any 
		// direct -- or indirect! -- overlap between the strings passed in & actively-tweening properties. 
		// There are some tricky things about it though -- Hit the docs, soldier! :)
		public function isHandling(properties : Array) : Boolean {
			if (!isNaN(_changeWidth)) {
				if (properties.indexOf("width")>-1) return true;
				if (properties.indexOf("scaleX")>-1) return true;
			}
			if (!isNaN(_changeHeight)) {
				if (properties.indexOf("height")>-1) return true;
				if (properties.indexOf("scaleY")>-1) return true;
			}
			return false;
		}
		
		// When there's a conflict the manager calls this method so you can stop the tween.
		public function releaseHandling(...params) : void {
			//trace(this + " releaseHandling()"); 
			super.stop();
		}
		
		// Try interrupting the tween with another tween on the same target to see if it works!
		// (Remember to run the setup command to activate OverlapMonitor, see header doc in this class.)
	}
}
