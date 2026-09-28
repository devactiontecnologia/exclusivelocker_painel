@if($paginator->hasPages())
<nav class="pagination">
    @if($paginator->onFirstPage())
        <span>Anterior</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}">Anterior</a>
    @endif
    <span>Pág. {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }} · {{ $paginator->total() }} registros</span>
    @if($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}">Próxima</a>
    @else
        <span>Próxima</span>
    @endif
</nav>
@endif
