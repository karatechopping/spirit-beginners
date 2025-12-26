<?php
/**
 * Simple SMTP Mailer
 * Lightweight SMTP implementation without external dependencies
 */

class SMTPMailer
{
    private $host;
    private $port;
    private $secure;
    private $username;
    private $password;
    private $socket;

    public function __construct($host, $port, $secure, $username, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->secure = $secure;
        $this->username = $username;
        $this->password = $password;
    }

    public function send($to, $subject, $body, $fromName, $fromEmail, $replyTo = null, $isHtml = false)
    {
        try {
            // Connect to SMTP server
            $this->connect();

            // Send SMTP commands
            $this->command("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), 250);

            // Authenticate
            $this->command("AUTH LOGIN", 334);
            $this->command(base64_encode($this->username), 334);
            $this->command(base64_encode($this->password), 235);

            // Send email
            $this->command("MAIL FROM: <{$fromEmail}>", 250);
            $this->command("RCPT TO: <{$to}>", 250);
            $this->command("DATA", 354);

            // Build message headers and body
            $message = "From: {$fromName} <{$fromEmail}>\r\n";
            $message .= "To: <{$to}>\r\n";
            if ($replyTo) {
                // Check if replyTo already contains angle brackets (name+email format)
                if (strpos($replyTo, '<') !== false) {
                    $message .= "Reply-To: {$replyTo}\r\n";
                } else {
                    $message .= "Reply-To: <{$replyTo}>\r\n";
                }
            }
            $message .= "Subject: {$subject}\r\n";
            $message .= "MIME-Version: 1.0\r\n";

            if ($isHtml) {
                $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            } else {
                $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            }

            $message .= "\r\n"; // Blank line between headers and body
            $message .= $body;

            // Send message body as raw data (not using command method)
            fputs($this->socket, $message . "\r\n");

            // Send end-of-message marker and expect 250
            $this->command(".", 250);

            $this->command("QUIT", 221);

            fclose($this->socket);
            return true;

        } catch (Exception $e) {
            if ($this->socket) {
                fclose($this->socket);
            }
            throw $e;
        }
    }

    private function connect()
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);

        if ($this->secure === 'ssl') {
            $this->socket = stream_socket_client(
                "ssl://{$this->host}:{$this->port}",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );
        } else if ($this->secure === 'tls') {
            $this->socket = stream_socket_client(
                "tcp://{$this->host}:{$this->port}",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (!$this->socket) {
                throw new Exception("Failed to connect: $errstr ($errno)");
            }

            // Read welcome message
            fgets($this->socket);

            // Start TLS
            $this->command("STARTTLS", 220);
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        } else {
            $this->socket = stream_socket_client(
                "tcp://{$this->host}:{$this->port}",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT
            );
        }

        if (!$this->socket) {
            throw new Exception("Failed to connect to SMTP server: $errstr ($errno)");
        }

        // Read welcome message if not already read
        if ($this->secure !== 'tls') {
            fgets($this->socket);
        }
    }

    private function command($cmd, $expectedCode)
    {
        fputs($this->socket, $cmd . "\r\n");

        // Read response - handle multi-line responses
        $response = '';
        do {
            $line = fgets($this->socket);
            $response .= $line;
            $code = substr($line, 0, 3);

            // Check if this is the last line (no hyphen after code)
            $isLastLine = (substr($line, 3, 1) !== '-');
        } while (!$isLastLine && !feof($this->socket));

        if ($code != $expectedCode) {
            throw new Exception("SMTP Error: Expected {$expectedCode}, got {$code}: {$response}");
        }

        return $response;
    }
}
