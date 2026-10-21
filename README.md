# Statamic Form Submission Retention

A reusable Statamic addon for controlling how long stored form submissions are retained.

## Requirements

- PHP 8.3+
- Statamic 6
- A form and submission repository supported by Statamic

## Settings

After installing the addon, configure it in the Control Panel under **Addons → Form Submission Retention**.

- Automatic cleanup is disabled by default.
- The default retention period is 60 days.
- Enable cleanup and set a positive default number of days to activate the daily cleanup task.
- Each form has an optional **Submission retention** setting. Leave it empty to inherit the global period.

The addon deletes submissions older than the applicable period. For Statamic `files` fields, it also removes any temporary upload paths still attached to the submission. Statamic may already remove temporary files after sending form email attachments; this cleanup is safe to repeat.

## Scheduler

The addon registers `forms:cleanup-submissions` with Laravel's scheduler to run daily. The host application must run Laravel's scheduler, for example with the standard `schedule:run` cron entry.

The command can also be run manually:

```sh
php artisan forms:cleanup-submissions
```

# Contributing
Want to help improve this addon? Great! We simply ask that you review the [contribution guide](https://github.com/wearejust/statamic-form-submission-retention?tab=contributing-ov-file) before you open issues or send pull requests.
