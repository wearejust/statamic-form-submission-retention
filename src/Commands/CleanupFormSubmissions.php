<?php

namespace WeAreJust\StatamicFormSubmissionRetention\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Contracts\Forms\Submission as SubmissionContract;
use Statamic\Facades\Addon;
use Statamic\Facades\Form;

class CleanupFormSubmissions extends Command
{
    protected $signature = 'forms:cleanup-submissions';

    protected $description = 'Delete expired Statamic form submissions and their temporary uploads';

    public function handle(): int
    {
        $addon = Addon::get('wearejust/statamic-form-submission-retention');

        if (! $addon) {
            $this->error('The form submission retention addon could not be resolved.');

            return self::FAILURE;
        }

        $settings = $addon->settings();

        if (! filter_var($settings->get('enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->info('Form submission cleanup is disabled.');

            return self::SUCCESS;
        }

        $defaultDays = filter_var($settings->get('default_retention_days', 60), FILTER_VALIDATE_INT);

        if ($defaultDays === false || $defaultDays < 1) {
            $this->error('The global retention period must be a positive number of days.');

            return self::FAILURE;
        }

        $deleted = 0;

        foreach (Form::all() as $form) {
            $deleted += $this->cleanupForm($form, $defaultDays);
        }

        $this->info("Deleted {$deleted} expired form submission(s).");

        return self::SUCCESS;
    }

    private function cleanupForm(FormContract $form, int $defaultDays): int
    {
        $override = $form->get('retention_days');
        $days = $override === null || $override === ''
            ? $defaultDays
            : filter_var($override, FILTER_VALIDATE_INT);

        if ($days === false || $days < 1) {
            $this->warn("Skipping form [{$form->handle()}]: its retention period is invalid.");

            return 0;
        }

        $cutoff = now()->subDays($days);
        $deleted = 0;

        do {
            $submissions = $form->querySubmissions()
                ->where('date', '<', $cutoff)
                ->limit(100)
                ->get();

            foreach ($submissions as $submission) {
                $this->deleteTemporaryUploads($submission);
                $submission->delete();
                $deleted++;
            }
        } while ($submissions->isNotEmpty());

        return $deleted;
    }

    private function deleteTemporaryUploads(SubmissionContract $submission): void
    {
        $disk = Storage::disk(config('statamic.system.file_uploads_disk', 'local'));
        $basePath = trim(config('statamic.system.file_uploads_path', 'statamic/file-uploads'), '/');

        $submission->form()->blueprint()->fields()->all()
            ->filter(fn ($field) => $field->type() === 'files')
            ->each(function ($field) use ($submission, $disk, $basePath): void {
                $paths = collect($submission->get($field->handle()))
                    ->filter(fn ($path) => is_string($path) && $path !== '');

                foreach ($paths as $path) {
                    $relativePath = str_replace('\\', '/', ltrim($path, '/'));

                    if (str_contains($relativePath, '..')) {
                        continue;
                    }

                    $disk->delete($basePath.'/'.$relativePath);
                }
            });
    }
}
