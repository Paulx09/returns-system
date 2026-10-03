<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->singleton(\Resend\Contracts\Client::class, static function (): \Resend\Client {
                $apiKey = config('resend.api_key') ?? config('services.resend.key');

                if (! is_string($apiKey)) {
                    throw \Resend\Laravel\Exceptions\ApiKeyIsMissing::create();
                }

                $baseUri = \Resend\ValueObjects\Transporter\BaseUri::from(getenv('RESEND_BASE_URL') ?: 'api.resend.com');
                $headers = \Resend\ValueObjects\Transporter\Headers::withAuthorization(\Resend\ValueObjects\ApiKey::from($apiKey));
                $client = new \GuzzleHttp\Client(['verify' => false]);
                $transporter = new \Resend\Transporters\HttpTransporter($client, $baseUri, $headers);

                return new \Resend\Client($transporter);
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        if ($this->app->environment('production') || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        if ($recipient = config('mail.always_to')) {
            Mail::alwaysTo($recipient);
        }
    }
}
