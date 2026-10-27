@php
    $badgeClass = match ($status) {
        \App\Domain\Requests\RequestStatus::Draft,
        \App\Domain\Requests\RequestStatus::Cancelled => 'text-bg-secondary',
        \App\Domain\Requests\RequestStatus::Submitted => 'text-bg-primary',
        \App\Domain\Requests\RequestStatus::InfoRequested => 'text-bg-warning',
        \App\Domain\Requests\RequestStatus::Approved => 'text-bg-success',
        \App\Domain\Requests\RequestStatus::Rejected => 'text-bg-danger',
    };
@endphp

<span class="badge rounded-pill {{ $badgeClass }}">{{ $status->label() }}</span>
