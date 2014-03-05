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
	
	import org.goasap.PlayableBase;
	import org.goasap.events.SequenceEvent;
	import org.goasap.interfaces.IPlayable;	
	
	/**
	 * Subclasses should call the <code>dispatchAdvance</code> method when 
	 * the sequence should advance to its next step. It is mandatory that
	 * custom advance types dispatch this event one time, although each class
	 * may define its own conditions for when this event occurs.
	 * 
	 * @eventType org.goasap.events.SequenceEvent.ADVANCE
	 */
	[Event(name="ADVANCE", type="org.goasap.events.SequenceEvent")]

	/**
	 * Base class for other custom advance types, does nothing on its own.
	 * 
	 * @see OnConditionTrue
	 * @see OnDurationComplete
	 * @see OnEventComplete
	 * @see OnPlayableComplete
	 * 
	 * @author Moses Gunesch
	 */
	public class SequenceAdvance extends PlayableBase implements IPlayable {
		
		public function SequenceAdvance():void {
			super();
		}
		
		// -== Protected Methods ==-
		
		/**
		 * @private
		 * Call this method from subclasses to trigger advance, only once per play cycle.
		 * @param event		Allows method to be used as an event handler.
		 */
		protected function dispatchAdvance(event:Event=null) : void {
			stop();
			dispatchEvent(new SequenceEvent(SequenceEvent.ADVANCE));
		}
		
		public function start() : Boolean {
			return false;
		}
		
		public function stop() : Boolean {
			return false;
		}
		
		public function pause() : Boolean {
			return false;
		}
		
		public function resume() : Boolean {
			return false;
		}
		
		public function skipTo(position : Number) : Boolean {
			return false;
		}
	}
}
