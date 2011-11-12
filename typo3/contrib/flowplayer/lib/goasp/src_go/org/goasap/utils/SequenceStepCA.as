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
	import org.goasap.interfaces.IPlayable;
	import org.goasap.utils.customadvance.OnPlayableComplete;
	import org.goasap.utils.customadvance.SequenceAdvance;	
	
	/**
	 * Step class used with SequenceCA that adds custom sequence-advance options 
	 * so steps can determine when they advance, such as after a duration or after
	 * one item in the step completes.
	 * 
	 * <p>The property <code>advance</code> can be set to an instance of any subclass 
	 * of SequenceEvent, so that the ADVANCE event is sent when something specific 
	 * occurs other than group completion. (This feature adds a great deal of flexibility
	 * to sequences by enabling them to overlap their actions, instead of being forced
	 * to wait for all child activity in an action to finish before proceeding.)</p>
	 * 
	 * <p>Various advance types can be found in the <i>util.customadvance</i> package, and
	 * you are welcome to add your own by extending the base class SequenceAdvance.</p>
	 * 
	 * <ul>
	 * <li>OnPlayableComplete: ADVANCE occurs after a specific child completes.</li>
	 * <li>OnDurationComplete: ADVANCE occurs when a number of seconds has passed.</li>
	 * <li>OnEventComplete: ADVANCE occurs when any event-type is fired from any 
	 * dispatcher object, for example a LoaderInfo's COMPLETE event. You can optionally 
	 * set a custom handler to filter the event and return true when ready to advance.</li>
	 * <li>OnConditionTrue: ADVANCE occurs when a callback, executed on a loop, returns
	 * true.</li>
	 * </ul>
	 * <p>Note that if no custom advance type is set, the ADVANCE event is dispatched
	 * just before the group's regular COMPLETE event. If a custom advance type is set,
	 * the group will maintain the state PLAYING until it completes, regardless
	 * of whether ADVANCE is dispatched during play. It will also maintain that state
	 * after it completes until ADVANCE is dispatched, if that occurs after all children
	 * are done playing. (Be careful, custom advance types continue running indefinitely 
	 * if their conditions are never met, so track them closely.)</p>
	 * 
	 * @see SequenceCA
	 * @see org.goasap.utils.customadvance.OnConditionTrue OnConditionTrue
	 * @see org.goasap.utils.customadvance.OnDurationComplete OnDurationComplete
	 * @see org.goasap.utils.customadvance.OnEventComplete OnEventComplete
	 * @see org.goasap.utils.customadvance.OnPlayableComplete OnPlayableComplete
	 * @see org.goasap.utils.customadvance.SequenceAdvance SequenceAdvance
	 * 
	 * @author Moses Gunesch
	 */
	public class SequenceStepCA extends SequenceStep {
		
		// -== Public Properties ==-
		
		/**
		 * The advance property determines a custom advance behavior for the step 
		 * and must be set prior to <code>start</code>.
		 * 
		 * <p>The advance should be an instance of any subclass of the base class 
		 * SequenceAdvance (SequenceAdvance cannot be used directly) and must 
		 * dispatch SequenceEvent.ADVANCE.</p>
		 * 
		 * @see org.goasap.utils.customadvance.OnConditionTrue OnConditionTrue
		 * @see org.goasap.utils.customadvance.OnDurationComplete OnDurationComplete
		 * @see org.goasap.utils.customadvance.OnEventComplete OnEventComplete
		 * @see org.goasap.utils.customadvance.OnPlayableComplete OnPlayableComplete
		 * @see org.goasap.utils.customadvance.SequenceAdvance SequenceAdvance
		 */
		public function get advance() : SequenceAdvance {
			if (!_advance) {
				_advance = new OnPlayableComplete(this);
			}
			return _advance;
		}
		public function set advance(advance:SequenceAdvance) : void {
			if (super._state!=STOPPED || advance==_advance)
				return;
			if (_advance)
				_advance.removeEventListener(SequenceEvent.ADVANCE, dispatchAdvance);
			_advance = advance;
		}
		
		/**
		 * Verifies that this SequenceStep has not advanced yet.
		 */
		public function get willAdvance() : Boolean {
			return !_hasAdvanced;
		}
		
		override public function addChild(item:IPlayable, adoptChildState:Boolean=false): Boolean {
			if (adoptChildState && (item.state==PLAYING || item.state==PLAYING_DELAY)) {
				_state = PLAYING;
				_isSelf = true;
				_hasAdvanced = false;
				_advance = new OnPlayableComplete(this);
				return super.addChild(item, false);
			}
			return super.addChild(item, adoptChildState);
		}
		
		// -== Protected Properties ==-
		
		/**
		 * @private
		 */
		protected var _advance : SequenceAdvance;
		/**
		 * @private
		 */
		protected var _isSelf : Boolean = true; // this default is used in special case, super.addChild(x, true).
		/**
		 * @private
		 */
		protected var _hasAdvanced : Boolean = false;

		// -== Public Methods ==-
		
		/**
		 * Constructor. See PlayableGroup
		 * @see PlayableGroup
		 */
		public function SequenceStepCA(...items) : void {
			super((items[ 0 ] is Array) ? items[ 0 ] : items);
		}
		
		/**
		 * See PlayableGroup
		 * @see PlayableGroup#start
		 */
		override public function start() : Boolean {
			if (super.start()==false)
				return false;
			_isSelf = false;
			_hasAdvanced = false;
			if (advance is OnPlayableComplete)  // use getter here to force creation of default instance
				_isSelf = ((_advance as OnPlayableComplete).item==this);
			if (!_isSelf) { // Otherwise, the _advance instance is a dummy, completion is handled internally in this class. 
				_advance.addEventListener(SequenceEvent.ADVANCE, dispatchAdvance);
				_advance.start();
			}
			return true;
		}
		
		/**
		 * See PlayableGroup
		 * @see PlayableGroup#stop
		 */
		override public function stop() : Boolean {
			if (super.stop()==false) {
				return false;
			}
			if (!_hasAdvanced && !_isSelf) {
				_advance.removeEventListener(SequenceEvent.ADVANCE, dispatchAdvance);
				_advance.stop();
			}
			_hasAdvanced = false;
			return true;
		}
		
		/**
		 * See PlayableGroup
		 * @see PlayableGroup#pause
		 */
		override public function pause() : Boolean {
			var r:Boolean = super.pause();
			if (!_isSelf && !_hasAdvanced) {
				if (_advance.pause()==true) {
					_state = PAUSED;
					r = true;
				}
			}
			return r;
		}
		
		/**
		 * See PlayableGroup
		 * @see PlayableGroup#resume
		 */
		override public function resume() : Boolean {
			var r:Boolean = super.resume();
			if (!_isSelf && !_hasAdvanced) {
				if (_advance.resume()==true) {
					_state = PLAYING;
					r = true;
				}
			}
			return r;
		}
		
		/**
		 * See PlayableGroup
		 * @see PlayableGroup#skipTo
		 */
		override public function skipTo(position : Number) : Boolean {
			if (super.skipTo(position)==false)
				return false;
			advance.skipTo(position);
			return true;
		}
		
		/**
		 * @private
		 * Internal relay for SequenceEvent.ADVANCE dispatch.
		 */
		protected function dispatchAdvance(e:SequenceEvent) : void {
			if (_state==STOPPED)
				return;
			if (!_hasAdvanced && e.type==SequenceEvent.ADVANCE) {
				_hasAdvanced = true;
				if (super._listeners==0) // Complete the group, if it was not ready at complete().
					stop();
				dispatchEvent(new SequenceEvent(SequenceEvent.ADVANCE)); // order-sensitive: leave below stop().
				_advance.removeEventListener(SequenceEvent.ADVANCE, dispatchAdvance);
			}
		}
		
		// -== Protected Methods ==-
		
		/**
		 * @private
		 * Internal handler for group completion, overridden to allow item to continue
		 * playing until advance has been dispatched, if still waiting.
		 */
		override protected function complete() : void {
			if (_isSelf && super._listeners==0) { // _isSelf event is handled internally, _advance instance is a dummy.
				dispatchAdvance(new SequenceEvent(SequenceEvent.ADVANCE));
			}
			else if (_hasAdvanced) { // Do not stop if advance is still active.
				stop();
			}
		}
	}
}
