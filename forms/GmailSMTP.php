<?php
/**
 * Implementación SMTP para Gmail
 */
class GmailSMTP {
    private $smtp_user;
    private $smtp_pass;
    
    public function __construct($smtp_user, $smtp_pass) {
        $this->smtp_user = $smtp_user;
        $this->smtp_pass = $smtp_pass;
    }
    
    public function send($to, $subject, $message, $reply_to = null) {
        // Configurar headers
        $boundary = md5(time());
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Portafolio Sergio <{$this->smtp_user}>\r\n";
        if ($reply_to) {
            $headers .= "Reply-To: {$reply_to}\r\n";
        }
        
        // Crear conexión SMTP segura
        $socket = $this->createSecureConnection();
        if (!$socket) {
            return false;
        }
        
        try {
            // Proceso SMTP
            $this->smtpCommand($socket, null, "220");
            $this->smtpCommand($socket, "EHLO localhost\r\n", "250");
            $this->smtpCommand($socket, "STARTTLS\r\n", "220");
            
            // Habilitar encriptación
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Error al habilitar TLS");
            }
            
            $this->smtpCommand($socket, "EHLO localhost\r\n", "250");
            $this->smtpCommand($socket, "AUTH LOGIN\r\n", "334");
            $this->smtpCommand($socket, base64_encode($this->smtp_user) . "\r\n", "334");
            $this->smtpCommand($socket, base64_encode($this->smtp_pass) . "\r\n", "235");
            
            // Enviar email
            $this->smtpCommand($socket, "MAIL FROM:<{$this->smtp_user}>\r\n", "250");
            $this->smtpCommand($socket, "RCPT TO:<{$to}>\r\n", "250");
            $this->smtpCommand($socket, "DATA\r\n", "354");
            
            // Contenido del email
            $email_content = "Subject: $subject\r\n";
            $email_content .= $headers . "\r\n";
            $email_content .= $message . "\r\n";
            $email_content .= ".\r\n";
            
            $this->smtpCommand($socket, $email_content, "250");
            $this->smtpCommand($socket, "QUIT\r\n", "221");
            
            fclose($socket);
            return true;
            
        } catch (Exception $e) {
            fclose($socket);
            error_log("Error SMTP: " . $e->getMessage());
            return false;
        }
    }
    
    private function createSecureConnection() {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $socket = @stream_socket_client(
            "tcp://smtp.gmail.com:587",
            $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context
        );
        
        return $socket;
    }
    
    private function smtpCommand($socket, $command, $expected_code) {
        if ($command !== null) {
            fwrite($socket, $command);
        }
        
        $response = fread($socket, 1024);
        
        if ($expected_code && !str_starts_with($response, $expected_code)) {
            throw new Exception("Error SMTP. Esperado: $expected_code, Recibido: $response");
        }
        
        return $response;
    }
}
?>