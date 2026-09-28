<?php

namespace Youbar\EasyCrud\Resolution;

class ResolutionResult
{
    /**
     * @param  array<int, string>  $candidates
     */
    public function __construct(
        public readonly string $role,
        public readonly string $model,
        public readonly ?string $action,
        public readonly ?string $class,
        public readonly array $candidates = [],
        public readonly ?int $matched = null,
        public readonly bool $disabled = false,
    ) {}

    /**
     * @param  array<int, string>  $candidates
     */
    public static function found(
        string $role,
        string $model,
        ?string $action,
        string $class,
        array $candidates = [],
        ?int $matched = null,
    ): self {
        return new self($role, $model, $action, $class, $candidates, $matched);
    }

    /**
     * @param  array<int, string>  $candidates
     */
    public static function missing(string $role, string $model, ?string $action, array $candidates = []): self
    {
        return new self($role, $model, $action, null, $candidates);
    }

    public static function disabled(string $role, string $model, ?string $action): self
    {
        return new self($role, $model, $action, null, [], null, true);
    }

    public function resolved(): bool
    {
        return $this->class !== null;
    }

    /**
     * Human-readable explanation, used by the conventions command.
     */
    public function explain(): string
    {
        if ($this->disabled) {
            return 'disabled (set easy-crud.conventions.'.$this->role.' to enable)';
        }

        if ($this->resolved()) {
            return $this->matched === null
                ? 'resolved'
                : sprintf('resolved (pattern %d of %d)', $this->matched + 1, count($this->candidates));
        }

        return $this->candidates === []
            ? 'no patterns configured'
            : 'not found';
    }
}
