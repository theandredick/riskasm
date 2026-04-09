<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    private int    $statusCode;
    private array  $headers;
    private string $body;

    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body       = $body;
        $this->statusCode = $statusCode;
        $this->headers    = $headers;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $this->body;
    }

    // ── Factory helpers ────────────────────────────────────────────────────────

    public static function html(string $content, int $status = 200): self
    {
        return new self($content, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $status,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public static function notFound(string $message = 'Not Found'): self
    {
        return self::html("<h1>404 — $message</h1>", 404);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return self::html("<h1>403 — $message</h1>", 403);
    }

    public static function download(string $content, string $filename, string $mimeType): self
    {
        return new self($content, 200, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Content-Length'      => (string) strlen($content),
        ]);
    }
}
