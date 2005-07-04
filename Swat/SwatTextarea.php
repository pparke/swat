<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatState.php';

/**
 * A multi-line text entry widget
 *
 * @package   Swat
 * @copyright 2004-2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SwatTextarea extends SwatControl implements SwatState
{
	/**
	 * Text content of the widget
	 *
	 * @var string
	 */
	public $value = '';

	/**
	 * Required
	 *
	 * Whether a value is required for this widget.
	 *
	 * @var bool
	 */
	public $required = false;
	
	/**
	 * Rows
	 *
	 * The number of rows for the XHTML textarea tag.
	 *
	 * @var integer
	 */
	public $rows = 10;

	/**
	 * Columns
	 *
	 * The number of columns for the XHTML textarea tag.
	 *
	 * @var integer
	 */
	public $cols = 50;

	/**
	 * Displays this textarea
	 *
	 * Outputs an appropriate XHTML tag.
	 */
	public function display()
	{
		$textarea_tag = new SwatHtmlTag('textarea');
		$textarea_tag->name = $this->id;
		$textarea_tag->id = $this->id;
		// NOTE: The attributes rows and cols are required in
		//       a textarea for XHTML strict.
		$textarea_tag->rows = $this->rows;
		$textarea_tag->cols = $this->cols;

		$textarea_tag->open();
		echo $this->value;
		$textarea_tag->close();
	}	

	/**
	 * Processes this textarea
	 *
	 * If a validation error occurs, an error message is attached to this
	 * widget.
	 */
	public function process()
	{
		$this->value = $_POST[$this->id];

		if ($this->required && !strlen($this->value)) {
			$msg = Swat::_('The %s field is required.');
			$this->addMessage(new SwatMessage($msg, SwatMessage::USER_ERROR));
		}
	}
	
	/**
	 * Gets the current state of this textarea
	 *
	 * @return boolean the current state of this textarea.
	 *
	 * @see SwatState::getState()
	 */
	public function getState()
	{
		return $this->value;
	}

	/**
	 * Sets the current state of this textarea
	 *
	 * @param boolean $state the new state of this textarea.
	 *
	 * @see SwatState::setState()
	 */
	public function setState($state)
	{
		$this->value = $state;
	}
}

?>
