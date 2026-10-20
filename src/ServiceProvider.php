<?php

namespace WeAreJust\StatamicFormSubmissionRetention;

use Illuminate\Console\Scheduling\Schedule;
use Statamic\Facades\Form;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        Commands\CleanupFormSubmissions::class,
    ];

    public function bootAddon(): void
    {
        Form::appendConfigFields('*', 'Submission retention', [
            'retention_days' => [
                'type' => 'integer',
                'display' => 'Override retention period (days)',
                'instructions' => 'Leave empty to use the global retention period.',
                'validate' => ['nullable', 'integer', 'min:1'],
            ],
        ]);
    }

    protected function schedule(Schedule $schedule): void
    {
        $schedule
            ->command('forms:cleanup-submissions')
            ->daily()
            ->withoutOverlapping();
    }
}
