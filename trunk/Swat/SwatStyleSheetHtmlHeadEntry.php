<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/SwatHtmlHeadEntry.php';

/**
 * Stores and outputs an HTML head entry for a stylesheet include
 *
 * @package   Swat
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SwatStyleSheetHtmlHeadEntry extends SwatHtmlHeadEntry
{
	// {{{ public function display()

	public function display($uri_prefix = '')
	{
		printf('<style type="text/css" media="all">@import \'%s%s\';</style>',
			$uri_prefix,
			$this->uri);
	}

	// }}}
	// {{{ public function displayInline()

	public function displayInline($path)
	{
		echo '<style type="text/css" media="all">';
		readfile($path.$this->getUri());
		echo '</style>';
	}

	// }}}
}

?>