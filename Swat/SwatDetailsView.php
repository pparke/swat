<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDetailsViewField.php';
require_once 'Swat/SwatUIParent.php';

/**
 * A widget to display field-value pairs
 *
 * @package   Swat
 * @copyright 2004-2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SwatDetailsView extends SwatControl implements SwatUIParent
{
	/**
	 * An object containing values to display
	 *
	 * A data object contains properties and values. The SwatDetailsViewField
	 * objects inside this SwatDetailsView contain mappings between their
	 * properties and the properties of this data object. This allows the
	 * to display specific values from this data object.
	 *
	 * @var object
	 *
	 * @see SwatDetailsViewField
	 */
	public $data = null;

	/**
	 * An array of fields to be displayed by this details view
	 *
	 * @var array
	 */
	private $fields = array();

	/**
	 * Appends a field to this details view
	 *
	 * @param SwatDetailViewField $field the field to append
	 */
	public function appendField(SwatDetailsViewField $field)
	{
		$this->fields[] = $field;

		$field->view = $this;
	}

	/**
	 * Gets the number of fields of this details view
	 *
	 * @return integer the number of fields of this details view.
	 */
	public function getFieldCount()
	{
		return count($this->fields);
	}

	/**
	 * Get the fields from this details view
	 *
	 * @return array a reference to an array of fields from this view.
	 */
	public function &getFields()
	{
		return $this->fields;
	}

	/**
	 * Get a reference to a field
	 *
	 * @ return SwatDetailsViewField Matching field
	 */
	public function getField($id)
	{
		$fields = $this->getFields();
		foreach ($fields as $field)
			if ($id == $field->id)
				return $field;

		throw new SwatException("Field with an id of '$id' not found.");
	}

	/**
	 * Displays this details view
	 *
	 * Displays details view as tabular XHTML.
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		$table_tag = new SwatHtmlTag('table');
		$table_tag->class = 'swat-detail-view';

		$table_tag->open();
		$this->displayContent();
		$table_tag->close();
	}

	/**
	 * Adds a child object to this object
	 *
	 * @param $child the child object to add to this object.
	 *
	 * @throws SwatException
	 *
	 * @see SwatUIParent::addChild()
	 */
	public function addChild($child)
	{
		if ($child instanceof SwatDetailsViewField) {
			$this->appendField($child);
		} else {
			$class_name = get_class($child);

			throw new SwatException("Unable to add '$class_name' object to ".
				'SwatDetailsView. Only SwatDetailsViewField objects '.
				'can be nested within SwatDetailsView objects.');
		}
	}

	/**
	 * Displays each field of this view
	 *
	 * Displays each field of this view as an XHTML table row.
	 */
	private function displayContent()
	{
		$count = 0;
		$tr_tag = new SwatHtmlTag('tr');

		foreach ($this->fields as $field) {
			$count++;
			$tr_tag->class = ($count % 2 == 1) ? 'odd' : null;
			$tr_tag->open();

			$field->display($this->data);

			$tr_tag->close();
		}
	}
}

?>
