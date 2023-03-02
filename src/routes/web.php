<?php

Route::group(['prefix' => 'marketplace/etsy'], function () {
    Route::get('import-products', 'EtsyImportProductsController@import');
    Route::post('do-import-products', 'EtsyImportProductsController@doImport');
});
