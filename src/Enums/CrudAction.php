<?php

namespace Youbar\EasyCrud\Enums;

enum CrudAction: string
{
    case Index = 'index';
    case Show = 'show';
    case Store = 'store';
    case Update = 'update';
    case Destroy = 'destroy';

    /**
     * The policy ability this action authorizes against.
     */
    public function ability(): string
    {
        return match ($this) {
            self::Index => 'viewAny',
            self::Show => 'view',
            self::Store => 'create',
            self::Update => 'update',
            self::Destroy => 'delete',
        };
    }

    /**
     * Whether the action writes, and therefore expects something to validate with.
     */
    public function writes(): bool
    {
        return in_array($this, [self::Store, self::Update], true);
    }

    /**
     * Whether the action operates on a single existing model.
     */
    public function needsModel(): bool
    {
        return in_array($this, [self::Show, self::Update, self::Destroy], true);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
