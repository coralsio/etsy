<?php

Breadcrumbs::register('etsy_import_products', function ($breadcrumbs) {
    $breadcrumbs->parent('marketplace');
    $breadcrumbs->push(trans('Esty::module.entity.title'), url(config('entity.models.entity.resource_url')));
});
