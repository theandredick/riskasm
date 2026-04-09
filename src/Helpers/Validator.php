<?php

declare(strict_types=1);

namespace App\Helpers;

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (empty(trim((string) ($this->data[$field] ?? '')))) {
            $this->errors[$field][] = "$label is required.";
        }
        return $this;
    }

    public function email(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "$label must be a valid email address.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if (strlen($value) < $min) {
            $this->errors[$field][] = "$label must be at least $min characters.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if (strlen($value) > $max) {
            $this->errors[$field][] = "$label must not exceed $max characters.";
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = "$label contains an invalid value.";
        }
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
