<?php

namespace App\Core;

/**
 * Rule-string validator, e.g. ['email' => 'required|email|max:120'].
 * Database-dependent checks (uniqueness) live in the services that own the data.
 */
final class Validator
{
    private array $errors = [];

    public function __construct(
        private array $data,
        private array $rules,
        private array $labels = [],
    ) {
        $this->run();
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    public function passes(): bool
    {
        return !$this->errors;
    }

    public function fails(): bool
    {
        return (bool) $this->errors;
    }

    /** @return array<string,string> first error per field */
    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return array_intersect_key($this->data, $this->rules);
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            $isEmpty = $value === null || (is_string($value) && trim($value) === '');

            if (in_array('nullable', $rules, true) && $isEmpty) {
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $message = $this->check($name, $arg, $field, $value, $isEmpty);
                if ($message !== null) {
                    $this->errors[$field] = $message;
                    break;
                }
            }
        }
    }

    private function check(string $rule, ?string $arg, string $field, mixed $value, bool $isEmpty): ?string
    {
        $label = $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
        $str = is_scalar($value) ? (string) $value : '';

        return match ($rule) {
            'nullable' => null,
            'required' => $isEmpty ? "$label is required." : null,
            'string'   => $value !== null && !is_string($value) ? "$label must be text." : null,
            'email'    => filter_var($str, FILTER_VALIDATE_EMAIL) ? null : "Enter a valid email address.",
            'min'      => mb_strlen($str) < (int) $arg ? "$label must be at least $arg characters." : null,
            'max'      => mb_strlen($str) > (int) $arg ? "$label may not be longer than $arg characters." : null,
            'integer'  => filter_var($value, FILTER_VALIDATE_INT) === false ? "$label must be a whole number." : null,
            'min_value' => (int) $value < (int) $arg ? "$label must be at least $arg." : null,
            'max_value' => (int) $value > (int) $arg ? "$label may not be greater than $arg." : null,
            'in'       => in_array($str, explode(',', (string) $arg), true) ? null : "Choose a valid $label.",
            'username' => preg_match('/^[A-Za-z0-9_]{3,20}$/', $str)
                ? null : "$label must be 3–20 letters, numbers or underscores.",
            'confirmed' => $str === (string) ($this->data[$field . '_confirmation'] ?? '')
                ? null : "$label confirmation does not match.",
            'strong_password' => preg_match('/[a-z]/', $str) && preg_match('/[A-Z]/', $str) && preg_match('/\d/', $str)
                ? null : "$label needs an uppercase letter, a lowercase letter and a number.",
            default => throw new \InvalidArgumentException("Unknown validation rule [$rule]."),
        };
    }
}
