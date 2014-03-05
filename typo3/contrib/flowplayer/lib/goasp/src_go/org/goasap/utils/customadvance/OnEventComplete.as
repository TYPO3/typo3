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
package org.goasap.utils.customadvance {
	import flash.events.Event;
	import flash.events.IEventDispatcher;

	/**
	 * A custom advance type that triggers when any event is dispatched by any IEventDispatcher host. 
	 * 
	 * <p>If the event requires filtering or custom handling, you can optionally set up a custom handler 
	 * that accepts the event object as an input and returns true if all conditions are met for advancing 
	 * the sequence.</p>
	 * 
	 * @author Moses Gunesch
	 */
	public class OnEventComplete extends SequenceAdvance {

		// -== Protected Properties ==-
		
		/**
		 * @private
		 */
		protected var _host : IEventDispatcher;
		/**
		 * @private
		 */
		protected var _type : String;
		/**
		 * @private
		 */
		protected var _customHandler : Function;

		// -== Public Methods ==-
		
		/**
		 * @param dispatcher	Any object that dispatches the event.
		 * @param type			The event type to listen for
		 * @param customHanderThatReturnsBoolean	Optionally you may specify a custom event handler which should
		 * 											accept an event input and return true once all conditions are met.
		 */
		public function OnEventComplete(dispatcher:IEventDispatcher, type:String, customHanderThatReturnsBoolean:Function=null) : void {
			super();
			_host = dispatcher;
			_type = type;
			_customHandler = customHanderThatReturnsBoolean;
		}
		
		override public function start() : Boolean {
			_host.addEventListener(_type, dispatchAdvance);
			_state = PLAYING;
			return true;
		}
		
		override public function stop() : Boolean {
			_host.removeEventListener(_type, dispatchAdvance);
			_state = STOPPED;
			return true;
		}
		
		// -== Protected Methods ==-
		
		/**
		 * @private
		 */
		override protected function dispatchAdvance(event:Event=null) : void {
			if (_customHandler!=null) {
				try {
					if (_customHandler(event)===true)
						super.dispatchAdvance();
				}
				catch (e:Error) {
					// Could run a trace here instead.
					throw e;
				}
				return;
			}
			super.dispatchAdvance();
		}
	}
}
