/**
 * Creates a new check-all object
 *
 * The check-all object is responsible for handling change events and
 * notifying its controller on state change.
 *
 * @param string id the unique identifier of this check-all object.
 */
function SwatCheckAll(id)
{
	this.id = id;
	this.check_all = document.getElementById(id + '_value');
	this.controller = null;
}

/**
 * Set the state of this check-all object
 *
 * SwatCheckboxList uses this method to update the check-all's state when all
 * checkbox list items are checked/unchecked.
 *
 * @param boolean checked the new state of this check-all object.
 */
SwatCheckAll.prototype.setState = function(checked)
{
	this.check_all.checked = checked;
}

/**
 * Sets the controlling checkbox list
 *
 * This adds an event handler to the check-all to update the list when this
 * check-all is checked/unchecked. 
 *
 * @param SwatCheckboxList controller the JavaScript object that represents the
 *                                     checkbox list controlling this check-all
 *                                     object.
 */
SwatCheckAll.prototype.setController = function(controller)
{
	// only add the event handler the first time
	if (this.controller === null) {
		YAHOO.util.Event.addListener(this.check_all, 'click',
			SwatCheckAll.clickHandler, controller);

		YAHOO.util.Event.addListener(this.check_all, 'dblclick',
			SwatCheckAll.clickHandler, controller);
	}

	this.controller = controller;

	controller.check_all = this;
	controller.checkAllInit();
}

/**
 * Handles click events on a check-all object
 *
 * This is a static method.
 *
 * @param Event event the click event to handle.
 * @param SwatCheckboxList controller the JavaScript object that represents the
 *                                     checkbox list controlling this check-all
 *                                     object.
 */
SwatCheckAll.clickHandler = function(event, controller)
{
	var check_all = YAHOO.util.Event.getTarget(event);

	// check all checkboxes in the controller object
	controller.checkAll(check_all.checked);
}
