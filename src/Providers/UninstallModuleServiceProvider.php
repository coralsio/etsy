<?php

namespace Corals\Modules\Etsy\Providers;

use Corals\Foundation\Providers\BaseUninstallModuleServiceProvider;
use Corals\Modules\Etsy\database\migrations\EtsyTables;
use Corals\Modules\Etsy\database\seeds\EtsyDatabaseSeeder;

class UninstallModuleServiceProvider extends BaseUninstallModuleServiceProvider
{
    protected $migrations = [
        EtsyTables::class,
    ];

    protected function providerBooted()
    {
        $this->dropSchema();

        $etsyDatabaseSeeder = new EtsyDatabaseSeeder();

        $etsyDatabaseSeeder->rollback();
    }
}
