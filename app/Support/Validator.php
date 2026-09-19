<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Validation serveur systématique (indépendante du client).
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules  ex. ['email' => 'required|email|max:191']
     */
    public static function make(array $data, array $rules): self
    {
        $validator = new self();

        foreach ($rules as $field => $ruleString) {
            $labels = [
                'email' => 'adresse e-mail',
                'password' => 'mot de passe',
                'password_confirmation' => 'confirmation du mot de passe',
                'first_name' => 'prénom',
                'last_name' => 'nom',
                'phone' => 'numéro de téléphone',
                'token' => 'jeton de sécurité',
                'name' => 'nom du rôle',
                'label' => 'libellé du rôle',
                'description' => 'description',
                'permissions' => 'permissions',
            ];
            $label = $labels[$field] ?? str_replace(['_', '-'], ' ', $field);

            foreach (explode('|', $ruleString) as $rule) {
                [$ruleName, $parameters] = array_pad(explode(':', $rule, 2), 2, '');

                $validator->applyRule($data, $field, $label, $ruleName, $parameters);
            }
        }

        return $validator;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function has(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function firstMessage(): ?string
    {
        return $this->errors !== [] ? reset($this->errors) : null;
    }

    private function applyRule(array $data, string $field, string $label, string $rule, string $parameters): void
    {
        if ($this->has($field)) {
            return;
        }

        $value = $data[$field] ?? null;

        switch ($rule) {
            case 'required':
                if ($this->isEmpty($value)) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » est obligatoire.';
                }
                break;

            case 'email':
                if (!$this->isEmpty($value) && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être une adresse e-mail valide.';
                }
                break;

            case 'min':
                if (!$this->isEmpty($value) && mb_strlen((string) $value) < (int) $parameters) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit contenir au moins ' . $parameters . ' caractères.';
                }
                break;

            case 'max':
                if (!$this->isEmpty($value) && mb_strlen((string) $value) > (int) $parameters) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » ne peut pas dépasser ' . $parameters . ' caractères.';
                }
                break;

            case 'confirmed':
                $confirmation = $data[$field . '_confirmation'] ?? null;
                if ((string) $value !== (string) $confirmation) {
                    $this->errors[$field] = 'La confirmation du champ « ' . $label . ' » ne correspond pas.';
                }
                break;

            case 'phone':
                if (!$this->isEmpty($value) && preg_match('/^\+?[0-9\s\-().]{6,20}$/', (string) $value) !== 1) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » n\'est pas un numéro valide.';
                }
                break;

            case 'regex':
                $pattern = $parameters;
                if (!$this->isEmpty($value) && @preg_match($pattern, (string) $value) !== 1) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » contient un format invalide.';
                }
                break;

            case 'string':
                if (!$this->isEmpty($value) && !is_string($value)) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être une chaîne de caractères.';
                }
                break;

            case 'array':
                if (!$this->isEmpty($value) && !is_array($value)) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être une liste de valeurs.';
                }
                break;

            case 'nullable':
                // Aucune exigence si vide.
                break;

            case 'in':
                $allowed = array_map(static fn (string $v): string => trim($v), explode(',', $parameters));
                if (!$this->isEmpty($value) && !in_array((string) $value, $allowed, true)) {
                    $this->errors[$field] = 'La valeur du champ « ' . $label . ' » sélectionnée est invalide.';
                }
                break;

            case 'numeric':
                if (!$this->isEmpty($value) && !is_numeric($value)) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être un nombre.';
                }
                break;

            case 'int':
                if (!$this->isEmpty($value) && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être un nombre entier.';
                }
                break;

            case 'date':
                if (!$this->isEmpty($value)) {
                    $parsed = strtotime((string) $value);
                    if ($parsed === false || date('Y-m-d', $parsed) !== (string) $value) {
                        $this->errors[$field] = 'Le champ « ' . $label . ' » doit être une date valide.';
                    }
                }
                break;

            case 'boolean':
                if (!$this->isEmpty($value) && !in_array($value, [true, false, 0, 1, '0', '1', 'on'], true)) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être une valeur oui/non.';
                }
                break;

            case 'min_value':
                if (!$this->isEmpty($value) && is_numeric($value) && (float) $value < (float) $parameters) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être supérieur ou égal à ' . $parameters . '.';
                }
                break;

            case 'max_value':
                if (!$this->isEmpty($value) && is_numeric($value) && (float) $value > (float) $parameters) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » ne peut pas dépasser ' . $parameters . '.';
                }
                break;

            default:
                throw new \LogicException(sprintf('Règle de validation inconnue : %s', $rule));
        }
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }
}