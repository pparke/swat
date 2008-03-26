<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/SwatInputControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatState.php';

/**
 * A checkbox entry widget
 *
 * @package   Swat
 * @copyright 2004-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SwatCheckbox extends SwatInputControl implements SwatState
{
	// {{{ public properties

	/**
	 * Checkbox value
	 *
	 * The state of the widget.
	 *
	 * @var boolean
	 */
	public $value = false;

	/**
	 * Access key
	 *
	 * Access key for this checkbox input, for keyboard nagivation.
	 *
	 * @var string
	 */
	public $access_key = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new checkbox 
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);
		$this->requires_id = true;
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this checkbox
	 *
	 * Outputs an appropriate XHTML tag.
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		$this->getForm()->addHiddenField($this->id.'_submitted', 1);

		$input_tag = new SwatHtmlTag('input');
		$input_tag->type = 'checkbox';
		$input_tag->name = $this->id;
		$input_tag->id = $this->id;
		$input_tag->value = '1';
		$input_tag->accesskey = $this->access_key;

		if ($this->value)
			$input_tag->checked = 'checked';

		$input_tag->display();
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this checkbox
	 *
	 * Sets the internal value of this checkbox based on submitted form data.
	 */
	public function process()
	{
		if ($this->getForm()->getHiddenField($this->id.'_submitted') === null)
			return;

		$data = &$this->getForm()->getFormData();
		$this->value = array_key_exists($this->id, $data);
	}

	// }}}
	// {{{ public function getState()

	/**
	 * Gets the current state of this checkbox
	 *
	 * @return boolean the current state of this checkbox.
	 *
	 * @see SwatState::getState()
	 */
	public function getState()
	{
		return $this->value;
	}

	// }}}
	// {{{ public function setState()

	/**
	 * Sets the current state of this checkbox
	 *
	 * @param boolean $state the new state of this checkbox.
	 *
	 * @see SwatState::setState()
	 */
	public function setState($state)
	{
		$this->value = $state;
	}

	// }}}
	// {{{ public function getFocusableHtmlId()

	/**
	 * Gets the id attribute of the XHTML element displayed by this widget
	 * that should receive focus
	 *
	 * @return string the id attribute of the XHTML element displayed by this
	 *                 widget that should receive focus or null if there is
	 *                 no such element.
	 *
	 * @see SwatWidget::getFocusableHtmlId()
	 */
	public function getFocusableHtmlId()
	{
		return ($this->visible) ? $this->id : null;
	}

	// }}}
}

?>