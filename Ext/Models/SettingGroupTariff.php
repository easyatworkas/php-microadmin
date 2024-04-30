<?php // TODO: Not refactored

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

use Ext\Exceptions;
use Eaw\QueryBuilder;

/**
 * @property mixed|null $name
 * @property mixed|null $type
 * @property mixed|null $setting_group_id
 */
class SettingGroupTariff extends Model
{
    protected $path = '/setting_groups/{setting_group}/tariffs';
    protected string $shortPath = '/tariffs';
    protected ?array $my_rates = null;
    protected ?array $my_rules = null;

    // Direct Path
    function getRatePath(): string
    {
        return $this->shortPath . '/' . $this->getKey();
    }

    // QueryBuilders ---------------------------------------------------------------------------------------------------
    public function rates(): array
    {
        return $this->client->query($this->getRatePath() . '/rates')
            ->setModel(TariffRate::class)
            ->orderBy('created_at')
            ->direction('asc')
            ->getAll()
            ->all();
    }
    public function rules(): array
    {
        if (!is_null($this->my_rules)) return $this->my_rules;

        $this->my_rules = $this->client->query($this->getFullPath() . '/rules')->setModel(TariffRule::class)->getAll()->all();
        return $this->my_rules;
    }

    // Getters ---------------------------------------------------------------------------------------------------------
    public function getRate( string $search, string $property = 'id' ): ?TariffRate
    {
        foreach ($this->rates() as $rate)
        {
            if ($rate->{$property} == $search) return $rate;
        }
        return null;
    }
    public function getRule( string $search, string $property = 'id' ): ?TariffRule
    {
        foreach ($this->rules() as $rule)
        {
            if ($rule->{$property} == $search) return $rule;
        }
        return null;
    }

    // Internal Checkers -----------------------------------------------------------------------------------------------
    public function hasRateWith( array $parameters, ?string $atPointInTime = 'now' ): bool
    {
        foreach ($this->rates() as $rate) {
            if ($rate->matches($parameters)) {
                if (dateTimeWithin($atPointInTime, $rate['from'], $rate['to'])) return true;
            }
        }
        return false;
    }
    public function hasRuleWith( array $parameters ): bool
    {
        foreach ($this->rules() as $rule)
        {
            if ($rule->matches($parameters)) return true;
        }
        return false;
    }

    // Internal Modifiers ----------------------------------------------------------------------------------------------
    public function addRate(TariffRate $rate, string $from = 'now', $to = null): bool
    {
        if (!$this->hasRateWith([ 'from' => $rate->from ]))
        {
            eaw()->create("/tariffs/$this->id/rates", null, array_filter([
                'from' => $rate->from,
                'to' => $rate->to,
                'rate' => $rate->rate,
            ], static function($var) {return $var !== null;} ));
            return true;
        }
        return false;
    }
    public function addRule( TariffRule $rule ): bool
    {
        if (!$this->hasRuleWith([ 'name' => $rule->name, 'span' => $rule->value ]))
        {
            eaw()->create("/setting_groups/$this->setting_group_id/tariffs/$this->id/rules", null, [
                'name' => $rule->name,
                'value' => $rule->value,
            ]);
            return true;
        }
        return false;
    }
}
