<?php

namespace App\Console\Commands;

use App\Events\EnrollmentStatusChanged;
use App\Models\CameraEnrollment;
use Illuminate\Console\Command;

class CheckEnrollmentTimeoutsCommand extends Command
{
    /** The console command signature. */
    protected $signature = 'enrollment:check-timeouts';

    /** The console command description. */
    protected $description = 'Mark pending enrollments as failed if ACK timeout exceeded';

    /** Execute the console command. */
    public function handle(): int
    {
        $timeoutMinutes = config('hds.enrollment.ack_timeout_minutes');
        $cutoff = now()->subMinutes($timeoutMinutes);

        $timedOut = CameraEnrollment::where('status', CameraEnrollment::STATUS_PENDING)
            ->where('updated_at', '<', $cutoff)
            ->get();

        if ($timedOut->isEmpty()) {
            $this->info('No timed-out enrollments found.');

            return self::SUCCESS;
        }

        $errorMessage = 'Enrollment timed out. The camera did not respond within the expected window. Try again.';

        foreach ($timedOut as $enrollment) {
            $enrollment->update([
                'status' => CameraEnrollment::STATUS_FAILED,
                'last_error' => $errorMessage,
            ]);

            EnrollmentStatusChanged::dispatch(
                $enrollment->personnel_id,
                $enrollment->camera_id,
                CameraEnrollment::STATUS_FAILED,
                null,
                $errorMessage,
            );
        }

        $this->info("Marked {$timedOut->count()} enrollment(s) as timed out.");

        return self::SUCCESS;
    }
}
