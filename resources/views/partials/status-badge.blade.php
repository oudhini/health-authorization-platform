@php
    $config = match ($status) {
        \App\Domain\Requests\RequestStatus::Draft => ['class' => 'text-bg-secondary', 'icon' => 'bi-pencil-square'],
        \App\Domain\Requests\RequestStatus::Cancelled => ['class' => 'text-bg-secondary', 'icon' => 'bi-x-circle'],
        \App\Domain\Requests\RequestStatus::Submitted => ['class' => 'text-bg-primary', 'icon' => 'bi-send'],
        \App\Domain\Requests\RequestStatus::InfoRequested => ['class' => 'text-bg-warning', 'icon' => 'bi-question-circle'],
        \App\Domain\Requests\RequestStatus::Approved => ['class' => 'text-bg-success', 'icon' => 'bi-check-circle-fill'],
        \App\Domain\Requests\RequestStatus::Rejected => ['class' => 'text-bg-danger', 'icon' => 'bi-dash-circle-fill'],
    };
@endphp

<span class="badge rounded-pill {{ $config['class'] }} d-inline-flex align-items-center gap-1 shadow-xs">
    <i class="bi {{ $config['icon'] }} small"></i>
    <span>{{ $status->label() }}</span>
</span>
