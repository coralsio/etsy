<?php

namespace Corals\Modules\Etsy\Providers;

use Corals\Foundation\Providers\BaseUpdateModuleServiceProvider;

class UpdateModuleServiceProvider extends BaseUpdateModuleServiceProvider
{
    protected $module_code = 'corals-etsy';
    protected $batches_path = __DIR__ . '/../update-batches/*.php';
}
