<?php
require_once 'GmailSMTP.php';

/**
 * Mailer simple usando SMTP real
 */
class SimpleMailer {
    private $smtp_user;
    private $smtp_pass;
    
    public function __construct($smtp_user, $smtp_pass) {
        $this->smtp_user = $smtp_user;
        $this->smtp_pass = $smtp_pass;
    }
    
    public function send($to, $subject, $message, $reply_to = null) {
        // Usar implementación SMTP real
        $gmail_smtp = new GmailSMTP($this->smtp_user, $this->smtp_pass);
        return $gmail_smtp->send($to, $subject, $message, $reply_to);
    }
}
?>