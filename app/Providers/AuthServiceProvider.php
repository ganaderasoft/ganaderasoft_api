<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Services\Reportes\ReporteHistorialLactanciaService::class => \App\Policies\ReportesPolicy::class,
        \App\Services\Reportes\ReporteGeneralService::class => \App\Policies\ReportesPolicy::class,
        \App\Services\Reportes\ReportePesajeLecheService::class => \App\Policies\ReportesPolicy::class,
        \App\Services\Reportes\ReporteReproductivoService::class => \App\Policies\ReportesPolicy::class,
        \App\Services\Reportes\ReporteFincasService::class => \App\Policies\ReportesPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
