<?php
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

// no direct access
defined('_JEXEC') or die('Restricted access');

$GLOBALS["ext_name"] = basename(__FILE__);
$GLOBALS["com_name"] = realpath(dirname(__FILE__) . DS . ".." . DS . ".." . DS . "components");
$GLOBALS["mod_name"] = dirname(__FILE__);
$GLOBALS["EXT_NAME"] = strtoupper($GLOBALS["ext_name"]);
$GLOBALS["COM_NAME"] = strtoupper($GLOBALS["com_name"]);
$GLOBALS["MOD_NAME"] = strtoupper($GLOBALS["mod_name"]);
$GLOBALS["left"] = false;
$GLOBALS["right"] = true;
$app->cid = 0;
$app->mid = $module->id;
$app->submitted = (bool)count($_POST) && isset($_POST["mid_$app->mid"]);
$me = basename(__FILE__);
$name = substr($me, 0, strrpos($me, '.'));
include(realpath(dirname(__FILE__) . DS . $name . ".inc"));

// Include the syndicate functions only once
require_once(dirname(__FILE__) . DS . "helper.php");

$helpdir = JPATH_BASE . DS . 'components' . DS . $GLOBALS["com_name"] . DS . 'helpers' . DS;
$libsdir = JPATH_BASE . DS . 'components' . DS . $GLOBALS["com_name"] . DS . 'lib' . DS;
@require_once($helpdir . 'fieldsbuilder.php');
@include_once($helpdir . 'fsubmitter.php');
@include_once($helpdir . 'fajaxuploader.php');
@include_once($helpdir . 'fuploader.php');
@include_once($helpdir . 'fcaptcha.php');
@include_once($helpdir . 'fsession.php');
@include_once($helpdir . 'fantispam.php');
@include_once($helpdir . 'fmailer.php');
@include_once($libsdir . 'functions.php');

// Avoids email cloak bug forum/3-bugs/1363-e-mail-cloak-in-textfield.html
if ($scope == "com_content") echo("{emailcloak=off}");

$document = JFactory::getDocument();

// Add a stylesheet
$css = JURI::base() . 'components/' . $GLOBALS["com_name"] . '/css/neon.css';
$document->addStyleSheet($css);

//$link = FGetLink(NULL, "#mid_" . $module->id);
// FGetLink doesn't work for blog view -> article, because active page is always blog view even if you are into an article
$link = $_SERVER["REQUEST_URI"] . "#mid_" . $module->id;

// Load language
//$lang = JFactory::getLanguage();
// Load component language too
$lang->load($GLOBALS["com_name"]);

// Fields properties
$captcha['show'] = (bool)$params->get("stdcaptchadisplay");
// $captcha['label'] = $params->get("stdcaptcha");
$captcha['src'] = JURI::base(true) . '/components/com_foxcontact/lib/captcha-drawer.php?cid=0&mid=' . $module->id;
$captcha['transparent'] = JURI::base(true) . '/media/com_foxcontact/images/transparent.gif';
//$captcha['id'] = 'fcaptcha_mid_' . $module->id;
$captcha['width'] = $params->get("stdcaptchawidth", "");
$captcha['height'] = $params->get("stdcaptchaheight", "");

$upload['show'] = (bool)$params->get("uploaddisplay");
$direction = intval(JFactory::getLanguage()->get('rtl', 0));
$style['float'] = $direction ? "right" : "left";
$upload['label'] = $params->get("upload");
$jsession = JFactory::getSession();
$fsession = new FSession($jsession->getId(), 0, $module->id);
$data = $fsession->Load('filelist');  // Read the list from the session
if ($data) $upload['filelist'] = explode("|", $data);
else $upload['filelist'] = array();

if (intval($params->get("top_textdisplay", "0"))) $toptext = $params->get("top_text", "");	
else $toptext = "";
if (!empty($toptext)) $toptext = '<div class="foxmessage" style="clear:both;">' . $toptext . '</div>';

if (intval($params->get("bottom_textdisplay", "0"))) $bottomtext = $params->get("bottom_text", "");	
else $bottomtext = "";
if (!empty($bottomtext)) $bottomtext = '<div class="foxmessage" style="clear:both;">' . $bottomtext . '</div>';

$page_subheading = $params->get("page_subheading", "");

// Module xml
$xml = JApplicationHelper::parseXMLInstallFile(JPATH_SITE . DS . 'modules' . DS . $app->scope . DS . $app->scope . '.xml');

$messages = array();
$submitter = new FSubmitter($params, $messages);
$fieldsBuilder = new FieldsBuilder($params, $messages);
$ajax_uploader = new FAjaxUploader($params, $messages);
$uploader = new FUploader($params, $messages);
$fcaptcha = new FCaptcha($params, $messages);
$antispam = new FAntispam($params, $messages, $fieldsBuilder);
$mailer = new FMailer($params, $messages, $fieldsBuilder);

// Build $FormText
$form_text = "";
$form_text .= $fieldsBuilder->Show();
$form_text .= $ajax_uploader->Show();
$form_text .= $fcaptcha->Show();
$form_text .= $antispam->Show();
// Usually we want the submit button at the bottom
$form_text .= $submitter->Show();

// Build $TopText and $BottomText
switch(0)
   {
	case $submitter->IsValid(): break;
   case $fieldsBuilder->IsValid(): break;
   case $ajax_uploader->IsValid(): break;
   case $uploader->IsValid(): break;
   case $fcaptcha->IsValid(): break;
   case $antispam->IsValid(): break;
	// Spam check passed or disabled
	case $mailer->Send($messages): break;
   default:  // None of the previous checks are failed
      // Avoid to show the Form and the button again
      $form_text = "";

		// Reset captcha solution in the session after read it, avoiding that a fucked lamer
		// abuse of the *same session* without request the captcha again, to send tons of email
		$fsession->PurgeValue("captcha_answer");

		HeaderRedirect($params);
   }


require(JModuleHelper::getLayoutPath($app->scope, $params->get('layout', 'default')));

