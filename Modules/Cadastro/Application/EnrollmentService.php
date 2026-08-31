<?php

namespace Modules\Cadastro\Application;

use Illuminate\Support\Facades\DB;
use Modules\Cadastro\Domain\CourseClass;
use Modules\Cadastro\Domain\Enrollment;
use Modules\Cadastro\Domain\Person;
use Modules\Cadastro\Domain\WaitingListEntry;

class EnrollmentService
{
    /**
     * Enroll a person when capacity is available; otherwise place them in the waiting list.
     *
     * @return Enrollment|WaitingListEntry
     */
    public function enroll(CourseClass $courseClass, Person $person): Enrollment|WaitingListEntry
    {
        return DB::transaction(function () use ($courseClass, $person) {
            $lockedClass = CourseClass::query()->lockForUpdate()->findOrFail($courseClass->ulid);

            $activeEnrollments = Enrollment::query()
                ->where('class_id', $lockedClass->ulid)
                ->whereNull('cancelled_at')
                ->count();

            if ($lockedClass->capacity === 0 || $activeEnrollments < $lockedClass->capacity) {
                return Enrollment::query()->firstOrCreate(
                    [
                        'class_id' => $lockedClass->ulid,
                        'person_id' => $person->ulid,
                    ],
                    [
                        'enrolled_at' => now(),
                    ]
                );
            }

            $lastPosition = WaitingListEntry::query()
                ->where('class_id', $lockedClass->ulid)
                ->whereNull('promoted_at')
                ->whereNull('cancelled_at')
                ->max('position') ?? 0;

            return WaitingListEntry::query()->firstOrCreate(
                [
                    'class_id' => $lockedClass->ulid,
                    'person_id' => $person->ulid,
                ],
                [
                    'position' => $lastPosition + 1,
                    'joined_at' => now(),
                ]
            );
        });
    }
}
