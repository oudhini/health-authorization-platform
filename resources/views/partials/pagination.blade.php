@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="d-flex justify-content-center justify-content-md-between align-items-center flex-wrap gap-2 mt-4 pt-2">
        <div class="text-body-secondary small d-none d-md-block">
            Page <span class="fw-semibold text-body">{{ $paginator->currentPage() }}</span> sur <span class="fw-semibold text-body">{{ $paginator->lastPage() }}</span>
        </div>
        <ul class="pagination mb-0 shadow-sm rounded-pill overflow-hidden">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                @if ($paginator->onFirstPage())
                    <span class="page-link px-3">« Précédent</span>
                @else
                    <a class="page-link px-3" href="{{ $paginator->previousPageUrl() }}" rel="prev">« Précédent</a>
                @endif
            </li>
            <li class="page-item disabled d-md-none">
                <span class="page-link">Page {{ $paginator->currentPage() }} sur {{ $paginator->lastPage() }}</span>
            </li>
            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                @if ($paginator->hasMorePages())
                    <a class="page-link px-3" href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant »</a>
                @else
                    <span class="page-link px-3">Suivant »</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
