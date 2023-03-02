<?php

namespace Corals\Modules\Etsy\Facades;

use Illuminate\Support\Facades\Facade;

class Etsy extends Facade
{
    /**
     * @return mixed
     */
    protected static function getFacadeAccessor()
    {
        return \Corals\Modules\Etsy\Classes\Etsy::class;
    }
}
