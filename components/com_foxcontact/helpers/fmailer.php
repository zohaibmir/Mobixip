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

$inc_dir = realpath(dirname(__FILE__));
require_once($inc_dir . DS . 'fdatapump.php');
require_once($inc_dir . DS . 'fsession.php');
require_once($inc_dir . DS . 'fieldsbuilder.php');
require_once($inc_dir . DS . 'flogger.php');

$lib_dir = realpath(dirname(__FILE__) . DS . '..' . DS . 'lib');
require_once($lib_dir . DS . 'functions.php');
//require_once($lib_dir . DS . 'swift' . DS . 'swift_required.php');

class FMailer extends FDataPump
	{
	protected $FieldsBuilder;
	  
	public function __construct(&$params, &$messages, &$fieldsbuilder)
		{
		parent::__construct($params, $messages);

		$this->Name = "FMailer";
		$this->FieldsBuilder = $fieldsbuilder;
		$this->Logger = new FLogger();
		}


	protected function LoadFields()
		{
		}


	public function Send(&$messages)
		{
		$debug_log = new FDebugLogger("fmailer");

		$lang = JFactory::getLanguage();
		$lang->load($GLOBALS["com_name"], JPATH_ADMINISTRATOR);

		// Uses the user sender name. If the field is disabled, uses Joomla admin name
		$fromname = isset($this->FieldsBuilder->Fields['sender0']) ? $this->FieldsBuilder->Fields['sender0']['Value'] : $this->Application->getCfg("fromname");
		$from = isset($this->FieldsBuilder->Fields['sender1']['Value']) ? $this->FieldsBuilder->Fields['sender1']['Value'] : $this->Application->getCfg("mailfrom");

		$mail = JFactory::getMailer();
		// In Joomla 1.7 From and Reply-to fields is set by default to the Global admin email
		// but a call to setSender() won't change the Reply-to field
		$mail->ClearReplyTos();

		//$message = Swift_Message::newInstance();

//		if ($this->Application->getCfg("mailer") == "smtp" && (bool)$this->Application->getCfg("smtpauth") == true)
		if ($this->Application->getCfg("mailer") == "smtp" && (bool)$this->Application->getCfg("smtpauth") && strpos($this->Application->getCfg("smtpuser"), "@") !== false)
			{
			// With SMTP auth may be needed to set the username as the sender
			$mail->setSender(array($this->Application->getCfg("smtpuser"), $this->Application->getCfg("fromname")));
			$mail->addReplyTo(array($from, $fromname));
			}
		else
			{
			$mail->setSender(array($from, $fromname));
			}
/*
		if ($this->Application->getCfg("mailer") == "smtp" && (bool)$this->Application->getCfg("smtpauth"))
			{
			$message->setFrom(array($this->Application->getCfg("mailfrom") => $this->Application->getCfg("fromname")));
			$message->setReplyTo(array($from => $fromname));
			}
		else
			{
			$message->setFrom(array($from => $fromname));
			}
*/
		if ($this->Params->get("to_address", NULL))
			$recipients = explode(",", $this->Params->get("to_address", ""));
		else
			$recipients = array();

		// http://docs.joomla.org/How_to_send_email_from_components
		foreach ($recipients as $recipient)
			{
			// Avoid to call $mail->add..() with an empty string, since explode(",", $string) returns al least 1 item, even if $string is empty
			if (empty($recipient)) continue;
			$mail->addRecipient($recipient);
			}
/*
		$rcpt_counter = count($recipients);
		$message->setTo($recipients);
*/
		if ($this->Params->get("cc_address", NULL))
			$cc_addresses = explode(",", $this->Params->get("cc_address", ""));
		else
			$cc_addresses = array();

		foreach ($cc_addresses as $cc)
			{
			// Avoid to call $mail->add..() with an empty string, since explode(",", $string) returns al least 1 item, even if $string is empty
			if (empty($cc)) continue;
			$mail->addCC($cc);
			}
/*
		$rcpt_counter += count($cc_addresses);
		$message->setCc($cc_addresses);
*/
		if ($this->Params->get("bcc_address", NULL))
			$bcc_addresses = explode(",", $this->Params->get("bcc_address", ""));
		else
			$bcc_addresses = array();

		foreach ($bcc_addresses as $bcc)
			{
			// Avoid to call $mail->add..() with an empty string, since explode(",", $string) returns al least 1 item, even if $string is empty
			if (empty($bcc)) continue;
			$mail->addBCC($bcc);
			}
/*
		$rcpt_counter += count($bcc_addresses);
		$message->setBcc($bcc_addresses);
*/
		jimport('joomla.mail.helper');
		$mail->setSubject(JMailHelper::cleanSubject($this->Params->get("email_subject", "")));
		//$message->setSubject(JMailHelper::cleanSubject($this->Params->get("email_subject", "")));

		// Body
		$body = "";
      foreach ($this->FieldsBuilder->Fields as $key => $field)
         {
         switch ($field['Type'])
            {
            case 'sender':
            case 'text':
            case 'textarea':
            case 'dropdown':
            case 'checkbox':
					$body .= $this->AddToBody($field);
            }
			}

		// a blank line
		$body .= PHP_EOL;

		// Read the list from the session
		$jsession = JFactory::getSession();
		$fsession = new FSession($jsession->getId(), $this->Application->cid, $this->Application->mid);
		$data = $fsession->Load('filelist');  // Read the list from the session
		if ($data) $filelist = explode("|", $data);
		else $filelist = array();

		$uploadmethod = intval($this->Params->get("uploadmethod", "1"));  // How the webmaster wants to receive attachments

		if (count($filelist) && ($uploadmethod & 1)) $body .= $lang->_($GLOBALS["COM_NAME"] . "_UPLOAD_LBL") . PHP_EOL;
		foreach ($filelist as &$file)
			{  // binary 01: http link, binary 10: attach, binary 11: both
			$filename = 'components' . DS . $GLOBALS["com_name"] . DS . 'uploads' . DS . $file;
			if ($uploadmethod & 1) $body .= JURI::base() . $filename . PHP_EOL;
			if ($uploadmethod & 2) $mail->addAttachment(JPATH_SITE . DS . $filename);
			//if ($uploadmethod & 2) $message->attach(Swift_Attachment::fromPath(JPATH_COMPONENT . DS . 'uploads' . DS . $file));
			}

		// *********************************************************
		// we can't do it yet
		// Clear file list for the next submission of the same users
		//$fsession->Clear('filelist');

		$body .= PHP_EOL;

		// Info about url
		$body .= $this->Application->getCfg("sitename") . " - " . $this->CurrentURL() . PHP_EOL;

		// Info about client
		$body .= "Client: " . $this->ClientIPaddress() . " - " . $_SERVER['HTTP_USER_AGENT'] . PHP_EOL;

		$body = JMailHelper::cleanBody($body);
		$mail->setBody($body);
		//$message->setBody($body);

		//$message->setPriority(3);

		$this->Logger->Write("---------------------------------------------------" . PHP_EOL . $body);
/*
		$methods = array(
		  Swift_SmtpTransport::newInstance('localhost', 25)
		  Swift_MailTransport::newInstance()
		);

		//$transport = Swift_SmtpTransport::newInstance('localhost', 25);
		$transport = Swift_FailoverTransport::newInstance($methods);
		$mailer = Swift_Mailer::newInstance($transport);
*/
		if (($error = $mail->Send()) !== true)
			{
			$msg = empty($mail->ErrorInfo) ? $error->getMessage() : $mail->ErrorInfo;
			$messages[] = JText::_($GLOBALS["COM_NAME"] . "_ERR_SENDING_MAIL") . ". " . $msg . " " . GetHelpLink($msg);
			$this->Logger->Write($mail->ErrorInfo);
			$debug_log->Write($mail->ErrorInfo);
			$result = false;
			}
		else
			{
			// Notify email send success
			$this->Logger->Write("Email sent.");
			$messages[] = $this->Params->get('email_sent_text');
			$result = true;
			}
/*
		$slogger = new Swift_Plugins_Loggers_ArrayLogger();
		$mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($slogger));

		try
			{  
			//Send the message
			$result = $mailer->send($message, $failures);
			}
		catch (Exception $e)
			{
			$this->Logger->Write($e->getMessage());
			}

		if ($result == $rcpt_counter)
			{
			// Notify email send success
			$this->Logger->Write("Email sent.");
			//$this->Logger->Write($slogger->dump());
			$messages[] = $this->Params->get('email_sent_text');
			}
		else
			{
			$msg = JText::_($GLOBALS["COM_NAME"] . "_ERR_SENDING_MAIL");
			$this->Logger->Write($msg . ". Failing addresses: " . implode(" ", $failures));
			$this->Logger->Write($slogger->dump());
			$messages[] = $msg;
			}

		return ($result == $rcpt_counter);
*/

		// ***********************************************************************************
		// Now we send the mail to the submitter
		// I know this is an incredibly long function, but I'll do better in the next release
		// ***********************************************************************************
		$copy_to_submitter =
			(bool)JRequest::getVar($this->SafeName("copy_to_submitter" . $this->GetId()), NULL, 'POST') ||  // Checkbox is enabled
			($this->Params->get("copy_to_submitter", NULL) == 1);                            // always send a copy parameter

		if (!$copy_to_submitter || !isset($this->FieldsBuilder->Fields['sender1']) || empty($this->FieldsBuilder->Fields['sender1']['Value']))
			{
			$fsession->Clear('filelist');
			return $result;
			}

		$fromname = $this->Application->getCfg("fromname");  // Joomla admin name
		$from = $this->Application->getCfg("mailfrom");

		$mail = JFactory::getMailer();

//		if ($this->Application->getCfg("mailer") == "smtp" && (bool)$this->Application->getCfg("smtpauth") == true)
		if ($this->Application->getCfg("mailer") == "smtp" && (bool)$this->Application->getCfg("smtpauth") && strpos($this->Application->getCfg("smtpuser"), "@") !== false)
			{
			// With SMTP auth may be needed to set the username as the sender
			$mail->setSender(array($this->Application->getCfg("smtpuser"), $this->Application->getCfg("fromname")));
			$mail->addReplyTo(array($from, $fromname));
			}
		else
			{
			$mail->setSender(array($from, $fromname));
			}

		$mail->addRecipient($this->FieldsBuilder->Fields['sender1']['Value']);

		jimport('joomla.mail.helper');
		$mail->setSubject(JMailHelper::cleanSubject($this->Params->get("email_copy_subject", "")));

		// Body
		$body = $this->Params->get("email_copy_text", "") . PHP_EOL;
		// a blank line
		$body .= PHP_EOL;
		if ($this->Params->get("email_copy_summary", NULL))
			{
	      foreach ($this->FieldsBuilder->Fields as $key => $field)
	         {
	         switch ($field['Type'])
	            {
	            case 'sender':
	            case 'text':
	            case 'textarea':
	            case 'dropdown':
	            case 'checkbox':
						$body .= $this->AddToBody($field);
	            }
				}
			}

		// a blank line
		$body .= PHP_EOL;
		// A null body will raise a mail error, so always add at least a signature.
		$body .= $this->Application->getCfg("sitename") . PHP_EOL;

		// Read the list from the session
		$jsession = JFactory::getSession();
		$fsession = new FSession($jsession->getId(), $this->Application->cid, $this->Application->mid);  // mid = 0 means read all rows regardless of mid
		$data = $fsession->Load('filelist');  // Read the list from the session
		if ($data) $filelist = explode("|", $data);
		else $filelist = array();

		// a further separation from signature above
		if (count($filelist)) $body .= "------" . PHP_EOL . $lang->_($GLOBALS["COM_NAME"] . "_UPLOAD_LBL") . PHP_EOL;
		foreach ($filelist as &$file)
			{
			$body .= substr($file, 14) . PHP_EOL;
			}
		// Clear file list for the next submission of the same users
		$fsession->Clear('filelist');
		$body .= PHP_EOL;

		$body = JMailHelper::cleanBody($body);
		$mail->setBody($body);

		if (($error = $mail->Send()) !== true)
			{
			$msg = empty($mail->ErrorInfo) ? $error->getMessage() : $mail->ErrorInfo;
			$messages[] = JText::_($GLOBALS["COM_NAME"] . "_ERR_SENDING_MAIL") . ". " . $msg . " " . GetHelpLink($msg);
			$this->Logger->Write($mail->ErrorInfo);
			$debug_log->Write($mail->ErrorInfo);
			}
		else
			{
			// Notify email send success
			$this->Logger->Write("Email copy sent to the submitter.");
			//$messages[] = str_replace(PHP_EOL, "<br />", $body);
			}

		return $result;
		}


    private function AddToBody(&$field)
        {
        if (!$field['Display']) return "";		  
        //return $field["Name"] . ": " . $field["Value"] . PHP_EOL;
        return JFilterInput::getInstance()->clean($field["Name"], "") . ": " . JFilterInput::getInstance()->clean($field["Value"], "") . PHP_EOL;
        }


    private function CurrentURL()
        {
        $url = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $url .= "s";
        $url .= "://";
        $url .= $_SERVER["SERVER_NAME"];
        if ($_SERVER["SERVER_PORT"] != "80") $url .= ":" . $_SERVER["SERVER_PORT"];
        $url .= $_SERVER["REQUEST_URI"];
        return $url;
        }


    private function ClientIPaddress()
        {
        if (isset($_SERVER["REMOTE_ADDR"])) return $_SERVER["REMOTE_ADDR"];
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) return $_SERVER["HTTP_X_FORWARDED_FOR"];
        if (isset($_SERVER["HTTP_CLIENT_IP"])) return $_SERVER["HTTP_CLIENT_IP"];
        return "?";
        } 

    }
?>
