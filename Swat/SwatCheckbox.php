<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatState.php';

/**
 * A checkbox entry widget
 *
 * @package   Swat
 * @copyright 2004-2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SwatCheckbox extends SwatControl implements SwatState
{
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

	/**
	 * Displays this checkbox
	 *
	 * Outputs an appropriate XHTML tag.
	 */
	public function display()
	{
		$input_tag = new SwatHtmlTag('input');
		$input_tag->type = 'checkbox';
		$input_tag->name = $this->id;
		$input_tag->id = $this->id;
		$input_tag->value = '1';

		if ($this->value)
			$input_tag->checked = 'checked';

		if (strlen($this->access_key) > 0)
			$input_tag->accesskey = $this->access_key;

		$input_tag->display();
	}

	/**
	 * Processes this checkbox
	 *
	 * Sets the internal value of this checkbox based on submitted form data.
	 */
	public function process()
	{
		$this->value = array_key_exists($this->id, $_POST);
	}

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
		return $this->id;
	}
}

?>
