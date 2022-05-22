<?php

namespace Stanejoun\LightPHP;

use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
	private PHPMailer $mailer;

	public function __construct(string $subject, string $body, string $from, ?string $replyTo = null)
	{
		$this->mailer = new PHPMailer();
		if (Config::get('email')->type === 'smtp') {
			$this->mailer->isSMTP();
			$this->mailer->Host = Config::get('email')->host;
			$this->mailer->Port = Config::get('email')->port;
			if (Config::get('email')->auth) {
				$this->mailer->SMTPAuth = true;
				$this->mailer->Username = Config::get('email')->login;
				$this->mailer->Password = Config::get('email')->secret;
				$this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			}
		} else {
			$this->mailer->isMail();
		}
		$this->setSubject($subject);
		$this->setFrom($from);
		$this->setBody($body);
		if ($replyTo !== null) {
			$this->addReplyTo($replyTo);
		}
		$this->mailer->isHTML(true);
	}

	public function setSubject($subject)
	{
		$this->mailer->Subject = $subject;
	}

	public function setFrom($from)
	{
		$this->mailer->setFrom($from, 'From address');
	}

	public function setBody($body)
	{
		$this->mailer->Body = $body;
	}

	public function addReplyTo($replyTo)
	{
		$this->mailer->addReplyTo($replyTo, 'Reply address');
	}

	public function addAddress($address, $name = '')
	{
		$this->mailer->addAddress($address, $name);
	}

	public function addCC($address)
	{
		$this->mailer->addCC($address);
	}

	public function addBCC($address)
	{
		$this->mailer->addBCC($address);
	}

	public function addAttachment($path)
	{
		$this->mailer->addAttachment($path);
	}

	public function send()
	{
		return $this->mailer->send();
	}
}