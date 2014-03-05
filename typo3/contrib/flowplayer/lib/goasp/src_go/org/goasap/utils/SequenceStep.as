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
package org.goasap.utils {
	import org.goasap.events.SequenceEvent;

	/**
	 * A PlayableGroup wrapper for playable items in a sequence step. Dispatches 
	 * SequenceEvent.ADVANCE when all items have dispatched either STOP or COMPLETE.
	 * 
	 * @see Sequence
	 * @see SequenceCA
	 * @see SequenceStepCA
	 * 
	 * @author Moses Gunesch
	 */
	public class SequenceStep extends PlayableGroup {
		
		/**
		 * Constructor. See PlayableGroup
		 * @see PlayableGroup
		 */
		public function SequenceStep(...items) : void {
			super((items[ 0 ] is Array) ? items[ 0 ] : items);
		}
		
		/**
		 * @private
		 * Internal handler for group completion, overridden to dispatch ADVANCE
		 */
		override protected function complete() : void {
			super.complete();
			if (super._listeners==0) {
				dispatchEvent(new SequenceEvent(SequenceEvent.ADVANCE));
			}
		}
	}
}
