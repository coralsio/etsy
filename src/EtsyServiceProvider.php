<?php

namespace Corals\Modules\Etsy;

use Corals\Foundation\Providers\BasePackageServiceProvider;
use Corals\Modules\Etsy\Console\Commands\EtsyCommand;
use Corals\Modules\Etsy\Facades\Etsy;
use Corals\Modules\Etsy\Providers\EtsyAuthServiceProvider;
use Corals\Modules\Etsy\Providers\EtsyRouteServiceProvider;
use Corals\Settings\Facades\Modules;
use Illuminate\Foundation\AliasLoader;

class EtsyServiceProvider extends BasePackageServiceProvider
{
    protected $defer = true;
    /**
     * @var
     */
    protected $packageCode = 'corals-etsy';

    /**
     * Bootstrap the application events.
     *
     * @return void
     */

    public function bootPackage()
    {
        $this->commands(EtsyCommand::class);

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'Etsy');

        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'Etsy');

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function registerPackage()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/etsy.php', 'etsy');

        $this->app->register(EtsyAuthServiceProvider::class);
        $this->app->register(EtsyRouteServiceProvider::class);

        $this->app->booted(function () {
            $loader = AliasLoader::getInstance();
            $loader->alias('Etsy', Etsy::class);
        });
    }

    public function registerModulesPackages()
    {
        Modules::addModulesPackages('corals/etsy');
    }
}
