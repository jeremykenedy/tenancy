<?php

namespace Stancl\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class Tenancy
{
    /** @var Tenant|Model|null */
    public $tenant;

    /** @var callable|null */
    public static $getBootstrappers = null;

    /** @var bool */
    public $initialized = false;

    public function initialize(Tenant $tenant): void
    {
        if ($this->initialized && $this->tenant->getTenantKey() === $tenant->getTenantKey()) {
            return;
        }

        $this->tenant = $tenant;

        $this->initialized = true;

        event(new Events\TenancyInitialized($this));
    }

    public function end(): void
    {
        $this->initialized = false;

        event(new Events\TenancyEnded($this));

        $this->tenant = null;
    }

    /** @return TenancyBootstrapper[] */
    public function getBootstrappers(): array
    {
        // If no callback for getting bootstrappers is set, we just return all of them.
        $resolve = static::$getBootstrappers ?? function (Tenant $tenant) {
            return array_map('app', config('tenancy.bootstrappers'));
        };

        return $resolve($this->tenant);
    }

    public function query(): Builder
    {
        return $this->model()->query();
    }

    /** @return Tenant|Model */
    public function model()
    {
        $class = config('tenancy.tenant_model');

        return new $class;
    }

    public function find($id): ?Tenant
    {
        return $this->model()->find($id);
    }
}
