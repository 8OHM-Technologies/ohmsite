<?php

namespace App\Listeners;

use App\Notifications\QueueJobFailedNotification;
use App\Notifications\ScheduledTaskNotification;
use App\Notifications\SecurityLockoutNotification;
use App\Services\TelegramAlertService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Events\JobFailed;
use Throwable;

class TelegramSystemEventsSubscriber
{
    /**
     * Handle scheduled task failure.
     */
    public function handleScheduledTaskFailed(ScheduledTaskFailed $event): void
    {
        if (! config('telegraph.notifications.scheduled_tasks.notify_on_failure', true)) {
            return;
        }

        $taskName = $this->getTaskName($event->task);
        $errorMessage = $event->exception->getMessage();

        $notification = new ScheduledTaskNotification(
            taskName: $taskName,
            status: 'failed',
            output: $errorMessage,
        );

        TelegramAlertService::dispatchNotification($notification);
    }

    /**
     * Handle scheduled task completion (optional).
     */
    public function handleScheduledTaskFinished(ScheduledTaskFinished $event): void
    {
        if (! config('telegraph.notifications.scheduled_tasks.notify_on_success', false)) {
            return;
        }

        $taskName = $this->getTaskName($event->task);
        $runtime = (float) $event->runtime;

        $notification = new ScheduledTaskNotification(
            taskName: $taskName,
            status: 'success',
            runtimeInSeconds: $runtime,
        );

        TelegramAlertService::dispatchNotification($notification);
    }

    /**
     * Handle queue job failures.
     */
    public function handleJobFailed(JobFailed $event): void
    {
        if (! config('telegraph.notifications.queue.notify_on_failure', true)) {
            return;
        }

        $jobName = $event->job->resolveName();
        $queue = $event->job->getQueue();
        $attempts = $event->job->attempts();
        $exceptionMessage = $event->exception->getMessage();

        $notification = new QueueJobFailedNotification(
            connectionName: $event->connectionName,
            queueName: $queue,
            jobName: $jobName,
            exceptionMessage: $exceptionMessage,
            attempts: $attempts,
        );

        TelegramAlertService::dispatchNotification($notification);
    }

    /**
     * Handle authentication lockouts (brute-force defense).
     */
    public function handleLockout(Lockout $event): void
    {
        if (! config('telegraph.notifications.auth.notify_on_lockout', true)) {
            return;
        }

        $request = $event->request;
        $ip = $request ? $request->ip() : null;
        $identifier = $request ? ($request->input('email') ?: $request->input('username')) : null;
        $userAgent = $request ? $request->userAgent() : null;

        $notification = new SecurityLockoutNotification(
            ip: $ip,
            identifier: $identifier,
            userAgent: $userAgent,
        );

        TelegramAlertService::dispatchNotification($notification);
    }

    /**
     * Get a human-readable name or command for a scheduled task.
     */
    protected function getTaskName(mixed $task): string
    {
        try {
            if (method_exists($task, 'getSummaryForDisplay')) {
                return $task->getSummaryForDisplay();
            }

            if (! empty($task->description)) {
                return $task->description;
            }

            if (! empty($task->command)) {
                return $task->command;
            }
        } catch (Throwable) {
            // Ignore error extracting name
        }

        return 'Scheduled Command';
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            ScheduledTaskFailed::class => 'handleScheduledTaskFailed',
            ScheduledTaskFinished::class => 'handleScheduledTaskFinished',
            JobFailed::class => 'handleJobFailed',
            Lockout::class => 'handleLockout',
        ];
    }
}
