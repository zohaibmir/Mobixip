<?php defined('_JEXEC') or die('Restricted access');
/*
This file is part of "Fox Joomla Extensions".

You can redistribute it and/or modify it under the terms of the GNU General Public License
GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html

You have the freedom:
	* to use this software for both commercial and non-commercial purposes
	* to share, copy, distribute and install this software and charge for it if you wish.
Under the following conditions:
	* You must attribute the work to the original author by leaving untouched the link "powered by",
	  except if you obtain a "registerd version" forum/14-licensing/151-remove-the-backlink-powered-by-fox-contact.html

Author: Demis Palma
Documentation at forum/2-documentation.html
*/

require_once(realpath(dirname(__FILE__) . DS . $file . '.inc'));
$prefix = strtoupper($option) . "_";
$lang = JFactory::getLanguage();
$lang->load($option . '.sys');  // com_foxcontact.sys.ini
$lang->load("mod_quickicon");  // xx-XX.mod_quickicon.ini
$freesoftware = sprintf($lang->_('JGLOBAL_ISFREESOFTWARE'), $lang->_(strtoupper($option)));  // $lang->_('COM_FOXCONTACT')
$review = sprintf($lang->_($prefix . 'REVIEW'), $lang->_(strtoupper($option)), "<a href=\"$review\" target=\"_blank\">", '</a>', "<a href=\"$forum\" target=\"_blank\">", '</a>');
$s_description = sprintf($lang->_($prefix . 'SHORTDESCRIPTION'),
	"<a href=\"index.php?option=com_menus&view=items\">" . $lang->_('MOD_QUICKICON_MENU_MANAGER') . '</a>',
	"<a href=\"index.php?option=com_modules\">" . $lang->_('MOD_QUICKICON_MODULE_MANAGER') . '</a>');

$direction = intval(JFactory::getLanguage()->get('rtl', 0));
$left  = $direction ? "right" : "left";
$right = $direction ? "left" : "right";

$translators = explode("|", $lang->_($prefix . 'TRANSLATOR_NAME'));
$urls = explode("|", $lang->_($prefix . 'TRANSLATOR_PERSONAL_URL'));
$t_string = $lang->_($prefix . 'TRANSLATION_NAME') . " | ";

if (!file_exists(JPATH_ADMINISTRATOR . DS . "language" . DS . $lang->get("tag") . DS . $lang->get("tag") . "." . $option . ".ini"))
	{
	// Translation missing
	$translators = $urls = array();
	$t_string = "<blink>" . $lang->get("name") . " translation is still missing.</blink> Please consider to contribute by writing and sharing your own translation. It takes a few minutes, but it will be useful for many people. | ";
	$translators[0] = "Learn more.";
	$urls[0] = $translation_url;
	}

$t_count = count($translators);
for ($t = 0; $t < $t_count; ++$t)
	{
	if ($urls[$t] != "#") $t_string .= '<a href="' . $urls[$t] . '" target="_blank">';
	$t_string .= $translators[$t];
	if ($urls[$t] != "#") $t_string .= '</a>';
	$t_string .= ' | ';
	}
?>

<p><img src="../media/<?php echo("$option/images/$file"); ?>-logo.png" style="float:<?php echo($left); ?>;margin-<?php echo($right); ?>:16px;"></p>
<div style="width:400px;float:<?php echo($right); ?>;margin-<?php echo($left); ?>:16px;border:1px solid #cccccc;background:#ffffff;padding:16px">
	<p>
		<?php echo($t_string); ?>
	</p>
	<p><?php echo($lang->_($prefix . 'LONGDESCRIPTION')); ?></p>
</div>
<h2><?php echo($lang->_(strtoupper($option /* 'COM_FOXCONTACT' */))); ?></h2>
<p><b><?php echo($s_description); ?></b></p>

<p>
<a href="<?php echo($documentation); ?>" target="_blank"><?php echo($lang->_($prefix . 'DOCUMENTATION')); ?></a> |
<a href="<?php echo($download); ?>" target="_blank"><?php echo($lang->_($prefix . 'DOWNLOAD')); ?></a> |
<a href="<?php echo($forum); ?>" target="_blank"><?php echo($lang->_($prefix . 'FORUM')); ?></a> |
<a href="<?php echo($backlink); ?>" target="_blank" style="white-space:nowrap;"><?php echo("Remove 'powered by fox contact' link"); ?></a>
</p>
<p><?php echo($freesoftware); ?></p>

<div style="float:<?php echo($right); ?>;margin-<?php echo($left); ?>:16px;">
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="MWFY8BEEDHNRY">
<input type="image" src="../media/com_foxcontact/images/donate.jpg" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
<img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/it_IT/i/scr/pixel.gif" width="1" height="1">
</form>
</div>
<p><?php echo($review); ?></p>
