<?php
header('Content-Type: text/plain; charset=utf-8');
require_once 'SimpleMailer.php';

// Cargar variables de entorno
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Cargar .env
loadEnv(__DIR__ . '/../.env');

class ContactForm {
    private function getRecipientEmail() {
        return $_ENV['RECIPIENT_EMAIL'] ?? 'tu-email@ejemplo.com';
    }
    private const MIN_NAME_LENGTH = 2;
    private const MIN_SUBJECT_LENGTH = 4;
    private const MIN_MESSAGE_LENGTH = 10;
    
    private $errors = [];
    
    public function process() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return "Método no permitido.";
        }
        
        $data = $this->sanitizeInput();
        
        if (!$this->validate($data)) {
            return $this->getErrorMessage();
        }
        
        if ($this->sendEmail($data)) {
            return "OK";
        }
        
        return "Error al enviar el mensaje. Intenta más tarde.";
    }
    
    private function sanitizeInput() {
        return [
            'name' => htmlspecialchars(trim($_POST["name"] ?? '')),
            'email' => filter_var(trim($_POST["email"] ?? ''), FILTER_SANITIZE_EMAIL),
            'subject' => htmlspecialchars(trim($_POST["subject"] ?? '')),
            'message' => htmlspecialchars(trim($_POST["message"] ?? ''))
        ];
    }
    
    private function validate($data) {
        if (empty($data['name']) || strlen($data['name']) < self::MIN_NAME_LENGTH) {
            $this->errors[] = "El nombre debe tener al menos " . self::MIN_NAME_LENGTH . " caracteres.";
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Email inválido.";
        }
        
        if (empty($data['subject']) || strlen($data['subject']) < self::MIN_SUBJECT_LENGTH) {
            $this->errors[] = "El asunto debe tener al menos " . self::MIN_SUBJECT_LENGTH . " caracteres.";
        }
        
        if (empty($data['message']) || strlen($data['message']) < self::MIN_MESSAGE_LENGTH) {
            $this->errors[] = "El mensaje debe tener al menos " . self::MIN_MESSAGE_LENGTH . " caracteres.";
        }
        
        return empty($this->errors);
    }
    
    private function sendEmail($data) {
        try {
            // Obtener credenciales del .env
            $smtp_user = $_ENV['SMTP_USER'] ?? '';
            $smtp_pass = $_ENV['SMTP_PASSWORD'] ?? '';
            
            if (empty($smtp_user) || empty($smtp_pass)) {
                throw new Exception('Credenciales SMTP no configuradas correctamente');
            }
            
            $mailer = new SimpleMailer($smtp_user, $smtp_pass);
            
            $emailBody = $this->buildEmailTemplate($data);
            
            return $mailer->send(
                $this->getRecipientEmail(),
                'Contacto portafolio: ' . $data['subject'],
                $emailBody,
                $data['email']
            );
        } catch (Exception $e) {
            error_log("Error enviando email: " . $e->getMessage());
            return false;
        }
    }
    
    private function buildEmailTemplate($data) {
        return "
        <html>
        <head>
            <title>Nuevo mensaje de contacto</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 8px 8px; }
                .field { margin: 15px 0; padding: 10px; background: white; border-radius: 4px; }
                .label { font-weight: bold; color: #495057; display: inline-block; min-width: 80px; }
                .message-box { background: white; padding: 15px; border-left: 4px solid #667eea; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='margin: 0;'>📧 Nuevo mensaje desde tu portafolio</h2>
                </div>
                <div class='content'>
                    <div class='field'>
                        <span class='label'>👤 Nombre:</span> {$data['name']}
                    </div>
                    <div class='field'>
                        <span class='label'>📧 Email:</span> {$data['email']}
                    </div>
                    <div class='field'>
                        <span class='label'>📋 Asunto:</span> {$data['subject']}
                    </div>
                    <div class='message-box'>
                        <strong>💬 Mensaje:</strong><br><br>
                        " . nl2br($data['message']) . "
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getErrorMessage() {
        return "Errores encontrados: " . implode(" ", $this->errors);
    }
}

$contactForm = new ContactForm();
$result = $contactForm->process();
echo trim($result);
?>
