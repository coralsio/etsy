<?php

namespace Corals\Modules\Etsy\Providers;

use Corals\Foundation\Providers\BaseInstallModuleServiceProvider;
use Corals\Modules\Etsy\database\migrations\EtsyTables;
use Corals\Modules\Etsy\database\seeds\EtsyDatabaseSeeder;

class InstallModuleServiceProvider extends BaseInstallModuleServiceProvider
{
    protected $module_public_path = __DIR__ . '/../public';

    protected $migrations = [
        EtsyTables::class,
    ];

    protected function providerBooted()
    {
        $this->createSchema();

        $etsyDatabaseSeeder = new EtsyDatabaseSeeder();

        $etsyDatabaseSeeder->run();
    }
}
