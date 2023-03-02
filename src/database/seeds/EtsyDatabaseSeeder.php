<?php

namespace Corals\Modules\Etsy\database\seeds;

use Corals\Menu\Models\Menu;
use Illuminate\Database\Seeder;

class EtsyDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(EtsyMenuDatabaseSeeder::class);
    }

    public function rollback()
    {
        Menu::query()->where('url', 'marketplace/etsy/import-products')->delete();
    }
}
