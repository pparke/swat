<?php
/**
 * @package Swat
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright silverorange 2004
 */
require_once('Swat/SwatContainer.php');
require_once('Swat/SwatHtmlTag.php');

/**
 * A container with a disclosure widget that may be made hidden or visible by
 * by the user.
 */
class SwatDisclosure extends SwatContainer {

	/**
	 * A visible name for the label.
	 * @var string
	 */
	public $title = null;

	/**
	 * An flag telling whether the disclosure is open.
	 * @var bool
	 */
	public $open = true;

	public function init() {
		// A name id is required for this widget.
		$this->generateAutoName();
	}

	public function display() {

		$this->displayJavascript();

		$control_div = new SwatHtmlTag('div');
		$control_div->class = 'swat-disclosure-control';

		$control_div->open();

		$anchor = new SwatHtmlTag('a');
		$anchor->href = 'javascript:toggleDisclosureWidget(\''.$this->name.'\');';
		$anchor->open();

		$img = new SwatHtmlTag('img');
	
		if ($this->open) {
			$img->src = 'swat/images/disclosure-opened.png';
			$img->alt = 'close';
		} else {
			$img->src = 'swat/images/disclosure-closed.png';
			$img->alt = 'open';
		}

		$img->width = '16';
		$img->height = '16';
		$img->id = $this->name.'_img';

		$img->display();

		if ($this->title != null)
			echo $this->title;

		$anchor->close();
		$control_div->close();

		$container_div = new SwatHtmlTag('div');

		if ($this->open)
			$container_div->class = 'swat-disclosure-container-opened';
		else
			$container_div->class = 'swat-disclosure-container-closed';

		$container_div->id = $this->name;

		$container_div->open();

		parent::display();

		$container_div->close();
	}

	public function displayJavascript() {
		?>
		<script type="text/javascript">
			function toggleDisclosureWidget(id) {
				var img = document.getElementById(id + '_img');
				var div = document.getElementById(id);
				if (div.className == 'swat-disclosure-container-opened') {
					div.className = 'swat-disclosure-container-closed';
					img.src = 'swat/images/disclosure-closed.png';
					img.alt = 'open';
				} else {
					div.className = 'swat-disclosure-container-opened';
					img.src = 'swat/images/disclosure-opened.png';
					img.alt = 'close';
				}
			}
		</script>
		<?php
	}
}

?>
