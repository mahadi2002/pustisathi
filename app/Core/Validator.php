<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Rule-based validation with Bangla messages.
 *
 *   $v = Validator::make($request->body(), [
 *       'mobile' => 'required|mobile',
 *       'name'   => 'required|min:2|max:100',
 *   ], ['mobile' => 'মোবাইল নম্বর']);
 *
 * Raw $_POST / $_GET is never used without passing through here.
 */
final class Validator
{
    private array $errors = [];
    private array $clean  = [];

    private function __construct(
        private array $data,
        private array $rules,
        private array $labels,
    ) {
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        $v = new self($data, $rules, $labels);
        $v->run();
        return $v;
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? $field;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $raw   = $this->data[$field] ?? null;
            $value = is_string($raw) ? trim($raw) : $raw;
            $rules = explode('|', $ruleString);

            $required = in_array('required', $rules, true);
            $isEmpty  = $value === null || $value === '' || $value === [];

            if ($isEmpty) {
                if ($required) {
                    $this->fail($field, $this->label($field) . ' দিতে হবে।');
                } else {
                    $this->clean[$field] = $value;
                }
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'required' || $rule === '') {
                    continue;
                }

                $arg = null;
                if (str_contains($rule, ':')) {
                    [$rule, $arg] = explode(':', $rule, 2);
                }

                if (!$this->apply($field, (string) $rule, $arg, $value)) {
                    continue 2;
                }
            }

            $this->clean[$field] = $value;
        }
    }

    private function apply(string $field, string $rule, ?string $arg, mixed $value): bool
    {
        $label = $this->label($field);
        $str   = is_scalar($value) ? (string) $value : '';

        return match ($rule) {
            // 018 = Robi, 016 = Airtel — re-verify these before launch, BTRC prefixes have shifted before after operator mergers.
            'mobile' => preg_match('/^01[68][0-9]{8}$/', $str) === 1
                ?: $this->fail($field, '১১ সংখ্যার সঠিক Robi/Airtel নম্বর দিন (01XXXXXXXXX)।'),

            'digits' => preg_match('/^[0-9]{' . (int) $arg . '}$/', $str) === 1
                ?: $this->fail($field, $label . ' ' . bn_num((int) $arg) . ' সংখ্যার হতে হবে।'),

            'int' => preg_match('/^-?[0-9]{1,15}$/', $str) === 1
                ?: $this->fail($field, $label . ' একটি সংখ্যা হতে হবে।'),

            'numeric' => is_numeric($str)
                ?: $this->fail($field, $label . ' একটি সংখ্যা হতে হবে।'),

            'min' => mb_strlen($str, 'UTF-8') >= (int) $arg
                ?: $this->fail($field, $label . ' কমপক্ষে ' . bn_num((int) $arg) . ' অক্ষরের হতে হবে।'),

            'max' => mb_strlen($str, 'UTF-8') <= (int) $arg
                ?: $this->fail($field, $label . ' সর্বোচ্চ ' . bn_num((int) $arg) . ' অক্ষরের হতে পারে।'),

            'between' => $this->between($field, $label, (string) $arg, $str),

            'email' => filter_var($str, FILTER_VALIDATE_EMAIL) !== false
                ?: $this->fail($field, 'সঠিক Email দিন।'),

            'date' => strtotime($str) !== false
                ?: $this->fail($field, $label . ' একটি সঠিক তারিখ হতে হবে।'),

            'in' => in_array($str, explode(',', (string) $arg), true)
                ?: $this->fail($field, $label . ' সঠিক নয়।'),

            'array' => is_array($value)
                ?: $this->fail($field, $label . ' সঠিক নয়।'),

            default => true,
        };
    }

    private function between(string $field, string $label, string $arg, string $str): bool
    {
        [$min, $max] = array_pad(explode(',', $arg), 2, '0');
        $n = (float) $str;
        if ($n < (float) $min || $n > (float) $max) {
            return $this->fail(
                $field,
                $label . ' ' . bn_num($min) . ' থেকে ' . bn_num($max) . '-এর মধ্যে হতে হবে।'
            );
        }
        return true;
    }

    private function fail(string $field, string $message): bool
    {
        $this->errors[$field][] = $message;
        return false;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            return (string) $messages[0];
        }
        return null;
    }

    /** Only fields that had a rule and passed it. */
    public function validated(): array
    {
        return $this->clean;
    }

    public function get(string $field, mixed $default = null): mixed
    {
        return $this->clean[$field] ?? $default;
    }

    /** Flash errors + old input so the form can be re-rendered populated. */
    public function flash(array $exceptKeys = ['_token', 'password']): void
    {
        Session::flash('_errors', $this->errors);
        Session::flash('_old', array_diff_key($this->data, array_flip($exceptKeys)));
    }
}
