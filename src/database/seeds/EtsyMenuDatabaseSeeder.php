<?php

namespace Corals\Modules\Etsy\database\seeds;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EtsyMenuDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('menus')->insert([
            [
                'parent_id' => 1,// admin
                'key' => null,
                'url' => 'marketplace/etsy/import-products',
                'active_menu_url' => 'marketplace/etsy/import-products*',
                'name' => 'Etsy Importer',
                'description' => 'Etsy Products Importer',
                'icon' => 'fa fa-upload',
                'target' => null,
                'roles' => '["1"]',
                'order' => 0
            ]
        ]);
    }
}
