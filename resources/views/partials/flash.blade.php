@if (session('status'))
    <div class="alert alert-success" role="status">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Impossible de continuer :</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
