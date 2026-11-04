<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validation de formulaires : collecte les erreurs par champ, en français.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, mixed> $labels */
    public function __construct(private readonly array $labels = [])
    {
    }

    /** Champ obligatoire. */
    public function required(string $field, mixed $value, ?string $label = null): self
    {
        $text = is_array($value) ? implode('', $value) : (string) $value;
        if (trim($text) === '') {
            $this->add($field, sprintf('%s est obligatoire.', $this->label($field, $label)));
        }
        return $this;
    }

    /** Longueur minimale (UTF-8). */
    public function minLength(string $field, mixed $value, int $min, ?string $label = null): self
    {
        if (mb_strlen((string) $value, 'UTF-8') < $min) {
            $this->add($field, sprintf('%s doit contenir au moins %d caractères.', $this->label($field, $label), $min));
        }
        return $this;
    }

    /** Longueur maximale (UTF-8). */
    public function maxLength(string $field, mixed $value, int $max, ?string $label = null): self
    {
        if (mb_strlen((string) $value, 'UTF-8') > $max) {
            $this->add($field, sprintf('%s ne doit pas dépasser %d caractères.', $this->label($field, $label), $max));
        }
        return $this;
    }

    /** E-mail valide. */
    public function email(string $field, mixed $value, ?string $label = null): self
    {
        if (trim((string) $value) !== '' && Sanitizer::email($value) === null) {
            $this->add($field, sprintf('%s n\'est pas une adresse e-mail valide.', $this->label($field, $label)));
        }
        return $this;
    }

    /** Valeur appartenant à une liste blanche. */
    public function inList(string $field, mixed $value, array $allowed, ?string $label = null): self
    {
        if (!in_array($value, $allowed, true)) {
            $this->add($field, sprintf('%s est invalide.', $this->label($field, $label)));
        }
        return $this;
    }

    /** Identifiant existant (valeur > 0) ; accepte null si facultatif. */
    public function optionalId(string $field, mixed $value, ?string $label = null): self
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return $this;
        }
        if (Sanitizer::id($value) === 0) {
            $this->add($field, sprintf('%s est invalide.', $this->label($field, $label)));
        }
        return $this;
    }

    /** Date facultative au format Y-m-d. */
    public function optionalDate(string $field, mixed $value, ?string $label = null): self
    {
        $raw = trim((string) $value);
        if ($raw !== '' && Sanitizer::date($raw) === null) {
            $this->add($field, sprintf('%s n\'est pas une date valide.', $this->label($field, $label)));
        }
        return $this;
    }

    /** Tarif facultatif : entier positif ou vide. */
    public function optionalPrice(string $field, mixed $value, ?string $label = null): self
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return $this;
        }
        if (Sanitizer::price($raw) === null) {
            $this->add($field, sprintf('%s doit être un montant en euros (ex. 790).', $this->label($field, $label)));
        }
        return $this;
    }

    /** Condition personnalisée. */
    public function check(string $field, bool $condition, string $message): self
    {
        if (!$condition) {
            $this->add($field, $message);
        }
        return $this;
    }

    /** Ajoute une erreur. */
    public function add(string $field, string $message): void
    {
        $this->errors[$field] = $this->errors[$field] ?? $message;
    }

    /** Vrai si aucune erreur. */
    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** Vrai si des erreurs existent. */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** Première erreur, pour un message global. */
    public function firstError(): ?string
    {
        if ($this->errors === []) {
            return null;
        }
        $first = reset($this->errors);
        return $first === false ? null : (string) $first;
    }

    /** Libellé lisible d'un champ. */
    private function label(string $field, ?string $label): string
    {
        return $label ?? $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }
}
