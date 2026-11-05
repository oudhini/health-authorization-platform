@if (session('status'))
    <div class="alert alert-success shadow-sm d-flex align-items-center gap-2 mb-4" role="status">
        <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
        <div>{{ session('status') }}</div>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger shadow-sm d-flex align-items-start gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-octagon-fill fs-5 flex-shrink-0 mt-1"></i>
        <div>
            <strong>Impossible de continuer :</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
