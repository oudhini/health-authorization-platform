<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Domain\Requests\Exceptions\InvalidStatusTransition;
use App\Domain\Requests\RequestStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Executable version of docs/requirements.md, section 5.2. */
final class RequestStatusTest extends TestCase
{
    /**
     * The seven transitions of section 5.2. Anything else is forbidden.
     *
     * @return list<array{RequestStatus, RequestStatus}>
     */
    private static function allowedPairs(): array
    {
        return [
            [RequestStatus::Draft, RequestStatus::Submitted],
            [RequestStatus::Draft, RequestStatus::Cancelled],
            [RequestStatus::Submitted, RequestStatus::Approved],
            [RequestStatus::Submitted, RequestStatus::Rejected],
            [RequestStatus::Submitted, RequestStatus::InfoRequested],
            [RequestStatus::InfoRequested, RequestStatus::Submitted],
            [RequestStatus::InfoRequested, RequestStatus::Cancelled],
        ];
    }

    /** @return array<string, array{RequestStatus, RequestStatus}> */
    public static function allowedTransitions(): array
    {
        $cases = [];
        foreach (self::allowedPairs() as [$from, $to]) {
            $cases["{$from->value} -> {$to->value}"] = [$from, $to];
        }

        return $cases;
    }

    /** @return array<string, array{RequestStatus, RequestStatus}> */
    public static function forbiddenTransitions(): array
    {
        $cases = [];
        foreach (RequestStatus::cases() as $from) {
            foreach (RequestStatus::cases() as $to) {
                if (in_array([$from, $to], self::allowedPairs(), true)) {
                    continue;
                }
                $cases["{$from->value} -> {$to->value}"] = [$from, $to];
            }
        }

        return $cases;
    }

    #[DataProvider('allowedTransitions')]
    public function test_allowed_transition_is_accepted(RequestStatus $from, RequestStatus $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
        $this->assertSame($to, $from->transitionTo($to));
    }

    #[DataProvider('forbiddenTransitions')]
    public function test_forbidden_transition_is_rejected(RequestStatus $from, RequestStatus $to): void
    {
        $this->assertFalse($from->canTransitionTo($to));

        $this->expectException(InvalidStatusTransition::class);
        $from->transitionTo($to);
    }

    public function test_final_statuses_have_no_way_out(): void
    {
        foreach ([RequestStatus::Approved, RequestStatus::Rejected, RequestStatus::Cancelled] as $status) {
            $this->assertTrue($status->isFinal(), $status->value);
            $this->assertSame([], $status->allowedTransitions(), $status->value);
        }
    }

    public function test_non_final_statuses_can_still_move(): void
    {
        foreach ([RequestStatus::Draft, RequestStatus::Submitted, RequestStatus::InfoRequested] as $status) {
            $this->assertFalse($status->isFinal(), $status->value);
        }
    }

    public function test_status_values_match_the_documented_names(): void
    {
        $this->assertSame(
            ['draft', 'submitted', 'info_requested', 'approved', 'rejected', 'cancelled'],
            array_map(fn (RequestStatus $status): string => $status->value, RequestStatus::cases()),
        );
    }
}
