<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'symbol',
        'decimals',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decimals' => 'integer',
        ];
    }

    /**
     * Select options keyed by ISO code, e.g. ['EUR' => 'EUR (€)', 'CZK' => 'CZK'].
     *
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return static::query()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Currency $currency): array => [$currency->code => $currency->label()])
            ->all();
    }

    public function label(): string
    {
        return $this->symbol ? "{$this->code} ({$this->symbol})" : $this->code;
    }
}
