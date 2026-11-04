<?php

declare(strict_types=1);

namespace FlowForge\Support;

/**
 * Reusable Data Validator.
 * Validates fields against required, max length, allowed values, dates, and email formats.
 * Returns structured field errors matching the framework's API and HTML error contracts.
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data = [], array $rules = [])
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        $validator = new self($data, $rules);
        $validator->validate();
        return $validator;
    }

    public function validate(?array $data = null, ?array $rules = null): bool
    {
        if ($data !== null) {
            $this->data = $data;
        }
        if ($rules !== null) {
            $this->rules = $rules;
        }

        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }

            $value = $this->data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $ruleName = $rule;
                $parameter = null;

                if (str_contains($rule, ':')) {
                    [$ruleName, $parameter] = explode(':', $rule, 2);
                }

                $this->applyRule($field, $value, $ruleName, $parameter);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $param): void
    {
        $humanField = ucfirst(str_replace('_', ' ', $field));

        switch ($rule) {
            case 'required':
                if ($value === null || (is_string($value) && trim($value) === '') || (is_array($value) && empty($value))) {
                    $this->addError($field, "The {$humanField} field is required.");
                }
                break;

            case 'max':
                $max = (int)$param;
                if ($value !== null && is_string($value) && mb_strlen($value) > $max) {
                    $this->addError($field, "The {$humanField} must not exceed {$max} characters.");
                }
                break;

            case 'min':
                $min = (int)$param;
                if ($value !== null && is_string($value) && mb_strlen($value) < $min) {
                    $this->addError($field, "The {$humanField} must be at least {$min} characters.");
                }
                break;

            case 'in':
                $allowed = explode(',', (string)$param);
                if ($value !== null && $value !== '' && !in_array((string)$value, $allowed, true)) {
                    $this->addError($field, "The selected {$humanField} is invalid.");
                }
                break;

            case 'date':
                if ($value !== null && $value !== '') {
                    $d = \DateTime::createFromFormat('Y-m-d', (string)$value);
                    if (!$d || $d->format('Y-m-d') !== (string)$value) {
                        $this->addError($field, "The {$humanField} must be a valid date in YYYY-MM-DD format.");
                    }
                }
                break;

            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$humanField} must be a valid email address.");
                }
                break;
        }
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get structured errors array: ['field' => ['error 1', ...]]
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message for a specific field.
     */
    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
