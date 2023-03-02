<?php

namespace Corals\Modules\Etsy\Console\Commands;

use Corals\Modules\Etsy\Jobs\HandleProductsImportFile;
use Corals\Modules\Marketplace\Models\Store;
use Illuminate\Console\Command;

class EtsyCommand extends Command
{
    protected $signature = 'etsy:import {file} {store_id}';
    protected $description = 'Etsy importer';


    /**
     * etsy command handler
     */
    public function handle()
    {
        $path = $this->argument('file');

        if (!file_exists($path)) {
            $this->error('File not found exception.');
            return;
        }

        $clearExistingImages = false;

        $storeId = $this->argument('store_id');

        $store = Store::query()->findOrFail($storeId);

        $user = $store->user;

        dispatch(new HandleProductsImportFile($path, $clearExistingImages, $storeId, $user));
    }
}
