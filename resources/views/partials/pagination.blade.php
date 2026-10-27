@if ($paginator->hasPages())
    <nav aria-label="Pagination">
        <ul class="pagination justify-content-between mb-0">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                @if ($paginator->onFirstPage())
                    <span class="page-link">« Précédent</span>
                @else
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">« Précédent</a>
                @endif
            </li>
            <li class="page-item disabled">
                <span class="page-link">Page {{ $paginator->currentPage() }} sur {{ $paginator->lastPage() }}</span>
            </li>
            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                @if ($paginator->hasMorePages())
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant »</a>
                @else
                    <span class="page-link">Suivant »</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
