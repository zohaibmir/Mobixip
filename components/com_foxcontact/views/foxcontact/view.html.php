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

defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

$helpdir = JPATH_BASE . DS . 'components' . DS . $GLOBALS["com_name"] . DS . 'helpers' . DS;
@require_once($helpdir . "fsubmitter.php");
@require_once($helpdir . "fieldsbuilder.php");
@require_once($helpdir . "fajaxuploader.php");
@require_once($helpdir . "fuploader.php");
@require_once($helpdir . "fmailer.php");
@require_once($helpdir . "fantispam.php");
@require_once($helpdir . "fcaptcha.php");
@require_once($helpdir . "fsession.php");

require_once(JPATH_COMPONENT . DS . "lib" . DS . "functions.php");

class FoxContactViewFoxContact extends JView
	{
   protected $Application;
   protected $cparams;
   protected $Submitter;
   protected $FieldsBuilder;
   protected $AjaxUploader;
   protected $Uploader;
   protected $Antispam;
   protected $Mailer;
	protected $FoxCaptcha;
	protected $messages = array();

   public $FormText = "";

	// Overwriting JView display method
	function display($tpl = null)
		{
		$this->Application = JFactory::getApplication();
		// The following code will access the Component-wide default parameters,
		// already overridden with those for the menu item (if applicable):
		$this->cparams = $this->Application->getParams('com_foxcontact');

      $this->Submitter = new FSubmitter($this->cparams, $this->messages);
      $this->FieldsBuilder = new FieldsBuilder($this->cparams, $this->messages);
      $this->AjaxUploader = new FAjaxUploader($this->cparams, $this->messages);
      $this->Uploader = new FUploader($this->cparams, $this->messages);
      $this->FoxCaptcha = new FCaptcha($this->cparams, $this->messages);
      $this->Antispam = new FAntispam($this->cparams, $this->messages, $this->FieldsBuilder);
      $this->Mailer = new FMailer($this->cparams, $this->messages, $this->FieldsBuilder);

      // Build $FormText
		$this->FormText .= $this->FieldsBuilder->Show();
		$this->FormText .= $this->AjaxUploader->Show();
		$this->FormText .= $this->FoxCaptcha->Show();
		$this->FormText .= $this->Antispam->Show();
		// Usually we want the submit button at the bottom
		$this->FormText .= $this->Submitter->Show();

      switch(0)
         {
			case $this->Submitter->IsValid(): break;
         case $this->FieldsBuilder->IsValid(): break;
         case $this->AjaxUploader->IsValid(): break;
         case $this->Uploader->IsValid(): break;
         case $this->FoxCaptcha->IsValid(): break;
         case $this->Antispam->IsValid(): break;
			// Spam check passed or disabled
			case $this->Mailer->Send($this->messages): break;
         default:  // None of the previous checks are failed
            // Avoid to show the Form and the button again
            $this->FormText = "";

				// Reset captcha solution in the session after read it, avoiding that a fucked lamer
				// abuse of the *same session* without request the captcha again, to send tons of email
				$jsession = JFactory::getSession();
				$fsession = new FSession($jsession->getId(), $this->Application->cid, $this->Application->mid);
				$fsession->PurgeValue("captcha_answer");

				HeaderRedirect($this->cparams);
         }

		// Check for errors.      
		if (count($errors = $this->get('Errors'))) 
			{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
			}
         
		// Display the view
		parent::display($tpl);
		}
	}
?>
