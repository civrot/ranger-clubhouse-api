<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

use App\Models\AccessDocumentDelivery;
use App\Models\Bmid;
use App\Models\Document;
use App\Models\Help;
use App\Models\PersonEvent;
use App\Models\Setting;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Route::bind('access-document-delivery', function($id) {
            return AccessDocumentDelivery::findForRoute($id) ?? abort(404);
        });

        Route::bind('bmid', function($id) {
            return Bmid::find($id) ?? abort(404);
        });

        Route::bind('help', function ($id) {
            return Help::findByIdOrSlug($id) ?? abort(404);
        });

        Route::bind('setting', function ($id) {
            return Setting::find($id) ?? abort(404);
        });

        Route::bind('person-event', function ($id) {
            return PersonEvent::findForRoute($id) ?? abort(404);
        });

        Route::bind('document', function ($id) {
            return Document::findIdOrTag($id) ?? abort(404);
        });
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        //$this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */

    /*
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }
    */

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }
}
