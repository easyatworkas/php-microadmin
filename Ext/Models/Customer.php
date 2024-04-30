<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasAbsences;                 // OK
use Ext\Traits\HasAvailabilities;           // OK
use Ext\Traits\HasCalendarEvents;
use Ext\Traits\HasCustomerBalanceTypes;
use Ext\Traits\HasCustomerBillingItems;
use Ext\Traits\HasCustomerBusinessUnits;
use Ext\Traits\HasCustomerChecklists;
use Ext\Traits\HasCustomerDefaultHrFiles;
use Ext\Traits\HasCustomerFileTypes;
use Ext\Traits\HasCustomerGroups;           // OK
use Ext\Traits\HasCustomerHolidays;
use Ext\Traits\HasCustomerKpiMirrors;
use Ext\Traits\HasCustomerKpiTypes;
use Ext\Traits\HasCustomerMlBuckets;
use Ext\Traits\HasCustomerPayTypeLinks;
use Ext\Traits\HasCustomerPeriodLocks;
use Ext\Traits\HasCustomerPositions;
use Ext\Traits\HasCustomerPostits;
use Ext\Traits\HasCustomerQualifications;
use Ext\Traits\HasCustomerRelationships;
use Ext\Traits\HasCustomerReports;
use Ext\Traits\HasCustomerRoles;
use Ext\Traits\HasCustomerRotations;
use Ext\Traits\HasCustomerSchedules;
use Ext\Traits\HasCustomerShifts;
use Ext\Traits\HasCustomerSignables;
use Ext\Traits\HasCustomerTodoStatuses;
use Ext\Traits\HasCustomerUserGroups;
use Ext\Traits\HasCustomerUsers;
use Ext\Traits\HasEmployees;
use Ext\Traits\HasHyperLinks;
use Ext\Traits\HasOffTimes;                 // OK
use Ext\Traits\HasProducts;
use Ext\Traits\HasProperties;
use Ext\Traits\HasTimepunches;              // OK
use Ext\Traits\IsFlushable;

/**
 * @property mixed|null $id                         PRIMARY KEY         OK
 * @property mixed|null $stack_id                   FOREIGN KEY         OK
 * @property mixed|null $setting_group_id           FOREIGN KEY         OK
 * @property mixed|null $name
 * @property mixed|null $number
 * @property mixed|null $address1
 * @property mixed|null $address2
 * @property mixed|null $postal_code
 * @property mixed|null $city
 * @property mixed|null $billing_contact
 * @property mixed|null $billing_customer_id        KEY (optional)      OK
 * @property mixed|null $locale_code                FOREIGN KEY         OK
 * @property mixed|null $time_zone
 * @property mixed|null $latitude
 * @property mixed|null $longitude
 * @property mixed|null $type
 * @property mixed|null $active
 * @property mixed|null $logo_id
 * @property mixed|null $created_at
 * @property mixed|null $update_at
 * @property mixed|null $deleted_at
 * @property mixed|null $organization_number
 * @property mixed|null $region_id                  FOREIGN KEY         OK
 * @property mixed|null $language_code              FOREIGN KEY         OK
 * @property mixed|null $country_code               FOREIGN KEY         OK
 * @property mixed|null $currency
 */
class Customer extends Model
{
    use IsFlushable;
    use HasAbsences;                    // OK
    use HasAvailabilities;              // OK
    use HasCalendarEvents;
    use HasCustomerBalanceTypes;
    use HasCustomerBillingItems;
    use HasCustomerBusinessUnits;
    use HasCustomerChecklists;
    use HasCustomerDefaultHrFiles;
    use HasCustomerFileTypes;
    use HasCustomerGroups;              // OK
    use HasCustomerHolidays;
    use HasCustomerKpiMirrors;
    use HasCustomerKpiTypes;
    use HasCustomerMlBuckets;
    use HasCustomerPayTypeLinks;
    use HasCustomerPeriodLocks;
    use HasCustomerPositions;
    use HasCustomerPostits;
    use HasCustomerQualifications;
    use HasCustomerRelationships;
    use HasCustomerReports;
    use HasCustomerRoles;
    use HasCustomerRotations;
    use HasCustomerSchedules;
    use HasCustomerShifts;
    use HasCustomerSignables;
    use HasCustomerTodoStatuses;
    use HasCustomerUserGroups;
    use HasCustomerUsers;
    use HasEmployees;
    use HasHyperLinks;
    use HasOffTimes;                    // OK
    use HasProducts;
    use HasProperties;
    use HasTimepunches;                 // OK

    protected $path = '/customers';

    /**
     * Gets Stack Model from property $stack_id
     *
     * @return \Ext\Models\Stack
     * @author Torbjørn Kallstad
     */
    public function stack(): Stack
    {
        return Stack::get($this->getAttribute('stack_id'));
    }

    /**
     * Gets SettingGroup Model from property $setting_group_id
     *
     * @return \Ext\Models\SettingGroup
     * @author Torbjørn Kallstad
     */
    public function settingGroup(): SettingGroup
    {
        return SettingGroup::get($this->getAttribute('setting_group_id'));
    }

    /**
     * Gets Customer Model from optional property $billing_customer_id
     *
     * @return \Ext\Models\Customer|null
     * @author Torbjørn Kallstad
     */
    public function billingCustomer(): ?Customer
    {
        return is_null($this->billing_customer_id) ? null : Customer::get($this->billing_customer_id);
    }

    /**
     * Gets Country Model from property $country_code
     *
     * @return \Ext\Models\Country
     * @author Torbjørn Kallstad
     */
    public function country(): Country
    {
        return Country::get($this->country_code);
    }

    /**
     * Gets CountryRegion Model from optional property $region_id
     *
     * @return \Ext\Models\CountryRegion|null
     * @author Torbjørn Kallstad
     */
    public function region(): ?CountryRegion
    {
        return is_null($this->region_id) ? null : $this->country()->getRegion($this->region_id);
    }

    /**
     * Gets Locale Model from property $locale_code
     *
     * @return \Ext\Models\Locale
     * @author Torbjørn Kallstad
     */
    public function locale(): Locale
    {
        return Locale::get($this->locale_code);
    }

    /**
     * Gets Language Model from optional property $language_code
     *
     * @return \Ext\Models\Language|null
     * @author Torbjørn Kallstad
     */
    public function language(): ?Language
    {
        return is_null($this->language_code) ? null : Language::get($this->language_code);
    }

    /**
     * Checks if the Customer has a logo
     * @return bool
     */
    public function hasLogo(): bool
    {
        return !is_null($this->logo_id);
    }

    /**
     * @return string
     */
    public function getLogo(): ?string
    {
        if (!$this->hasLogo()) {
            return null;
        }

        return eaw()->download('GET', "/customers/$this->id/logo");
    }

    public function setLogo(string $path): bool
    {
        try {
            eaw()->create("/customers/$this->id/logo", null, null, [ 'logo' => fopen($path, 'r') ]);
            return true;
        } catch (\Exception $e) {
            // TODO: log properly
            return false;
        }
    }

    public function addToPowerBIClient(int $clientId): bool
    {
        try
        {
            $client = OauthClient::get($clientId);

            $client->addPermission("customers.$this->id.^.get", true);

            return true;
        }
        catch (\Exception $e)
        {
            return false;
        }
    }

    public function createTpAppClient(bool $outputCredentials = false): ?OauthClient
    {
        try
        {
            $newClient = OauthClient::newInstance([
                'name' => $this->name . ' TpAppClient',
            ]);

            $newClient->save();

            $newClient->addPermission("customers.$this->id.business_units.*.get", true);
            $newClient->addPermission("customers.$this->id.employees.*.get", true);
            $newClient->addPermission("customers.$this->id.employees.*.shifts.*.get", true);
            $newClient->addPermission("customers.$this->id.employees.*.timepunches.*.get", true);
            $newClient->addPermission("customers.$this->id.employees.*.timepunches.*.update", true);
            $newClient->addPermission("customers.$this->id.employees.*.timepunches.create", true);
            $newClient->addPermission("customers.$this->id.get", true);

            if ($outputCredentials)
            {
                logg()->info("$newClient->name\t$newClient->id:$newClient->secret\n");
            }

            return $newClient;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    public function createTbsClient(bool $outputCredentials = false): ?OauthClient
    {
        try
        {
            $newClient = OauthClient::newInstance([
                'name' => $this->name . ' TBSClient',
            ]);

            $newClient->save();

            $newClient->addPermission("customers.$this->id.employees.*.timepunches.*.get", true);
            $newClient->addPermission("customers.$this->id.employees.*.timepunches.*.update", true);
            $newClient->addPermission("customers.$this->id.employees.*.timepunches.create", true);
            $newClient->addPermission("customers.$this->id.warnings.*.get", true);

            if ($outputCredentials)
            {
                logg()->info("$newClient->name\t$newClient->id:$newClient->secret\n");
            }

            return $newClient;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    public function createMyStoreClient(bool $outputCredentials = false): ?OauthClient
    {
        try
        {
            $newClient = OauthClient::newInstance([
                'name' => $this->name . ' MyStoreClient',
            ]);

            $newClient->save();

            $newClient->addPermission("customers.$this->id.business_date.get", true);
            $newClient->addPermission("customers.$this->id.employees.*.get", true);
            $newClient->addPermission("customers.$this->id.employees.*.timepunches.*.get", true);
            $newClient->addPermission("customers.$this->id.get", true);
            $newClient->addPermission("customers.$this->id.kpis.*.update", true);
            $newClient->addPermission("customers.$this->id.ml_buckets.*.get", true);
            $newClient->addPermission("customers.$this->id.ml_buckets.*.time_series.*.entries.*.get", true);
            $newClient->addPermission("customers.$this->id.ml_buckets.*.time_series.*.get", true);
            $newClient->addPermission("customers.$this->id.ml_buckets.*.time_series.*.projections.*.create", true);
            $newClient->addPermission("customers.$this->id.ml_buckets.*.variables.*.get", true);
            $newClient->addPermission("customers.$this->id.properties.*.get", true);
            $newClient->addPermission("customers.$this->id.sales_projections.*.update", true);
            $newClient->addPermission("customers.$this->id.timepunches.*.get", true);

            if ($outputCredentials)
            {
                logg()->info("$newClient->name\t$newClient->id:$newClient->secret\n");
            }

            return $newClient;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    public function createBackOfficeClient(bool $outputCredentials = false): ?OauthClient
    {
        try
        {
            $newClient = OauthClient::newInstance([
                'name' => $this->name . ' BackOfficeClient',
            ]);

            $newClient->save();

            $newClient->addPermission("customers.$this->id.backoffice_budgets.*.update", true);
            $newClient->addPermission("customers.$this->id.business_units.*.get", true);
            $newClient->addPermission("customers.$this->id.employees.*.get", true);
            $newClient->addPermission("customers.$this->id.employees.*.shifts.*.get", true);
            $newClient->addPermission("customers.$this->id.employees.*.timepunches.*.get", true);
            $newClient->addPermission("customers.$this->id.employees.*.timepunches.*.update", true);
            $newClient->addPermission("customers.$this->id.employees.*.timepunches.create", true);
            $newClient->addPermission("customers.$this->id.get", true);
            $newClient->addPermission("customers.$this->id.kpis.*.update", true);
            $newClient->addPermission("customers.$this->id.warnings.*.get", true);

            if ($outputCredentials)
            {
                logg()->info("$newClient->name\t$newClient->id:$newClient->secret\n");
            }

            return $newClient;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    // Used for PCKasse: https://api.easyatwork.com/pckasse?client_id=id&client_secret=secret
    public function createKPIClient(bool $outputCredentials = false): ?OauthClient
    {
        try
        {
            $newClient = OauthClient::newInstance([
                'name' => $this->name . ' KPIClient',
            ]);

            $newClient->save();

            $newClient->addPermission("customers.$this->id.kpis.*.update", true);

            if ($outputCredentials)
            {
                logg()->info("$newClient->name\t$newClient->id:$newClient->secret\n");
            }

            return $newClient;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    // Read-only client for external systems
    public function createAPIClient(bool $outputCredentials = false): ?OauthClient
    {
        try
        {
            $newClient = OauthClient::newInstance([
                'name' => $this->name . ' APIClient',
            ]);

            $newClient->save();

            $newClient->addPermission("customers.$this->id.^.get", true);

            if ($outputCredentials)
            {
                logg()->info("$newClient->name\t$newClient->id:$newClient->secret\n");
            }

            return $newClient;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }
}
