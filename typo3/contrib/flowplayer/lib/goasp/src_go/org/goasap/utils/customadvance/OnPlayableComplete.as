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
	import org.goasap.events.GoEvent;
	import org.goasap.interfaces.IPlayable;

	/**
	 * A custom advance type that triggers when any playable item (presumably one item
	 * in the step) dispatches STOP or COMPLETE.
	 * 
	 * @author Moses Gunesch
	 */
	public class OnPlayableComplete extends SequenceAdvance {

		// -== Public Properties ==-
		
		public function set item(item : IPlayable) : void {
			if (_state==STOPPED)
				_item = item;
		}
		public function get item() : IPlayable {
			return _item;
		}
		
		// -== Public Methods ==-
		
		/**
		 * @private
		 */
		protected var _item : IPlayable;
		
		/**
		 * @param item		Any playable item that dispatches STOP or COMPLETE,  
		 * 					normally a child item in the step using this custom advance.
		 */
		public function OnPlayableComplete(item : IPlayable = null) : void {
			super();
			_item = item;
		}
		
		override public function start() : Boolean {
			if (_item==null)
				return false;
			_item.addEventListener(GoEvent.STOP, super.dispatchAdvance);
			_item.addEventListener(GoEvent.COMPLETE, super.dispatchAdvance);
			_state = PLAYING;
			return true;
		}
		
		override public function stop() : Boolean {
			_item.removeEventListener(GoEvent.STOP, super.dispatchAdvance);
			_item.removeEventListener(GoEvent.COMPLETE, super.dispatchAdvance);
			_state = STOPPED;
			return true;
		}
	}
}
