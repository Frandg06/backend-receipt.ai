<?php

namespace App\Exceptions;

use Exception;

final class GroqServiceException extends Exception
{
    public static function invalidApiKey(): self
    {
        return new self('Invalid Groq API key configuration.');
    }

    public static function connectionFailed(int $statusCode, string $message): self
    {
        return new self("Groq API connection failed: {$statusCode} - {$message}");
    }

    public static function emptyResponse(): self
    {
        return new self('The AI returned an empty response.');
    }

    public static function invalidJson(string $content): self
    {
        return new self('The AI did not return valid JSON.');
    }
}
