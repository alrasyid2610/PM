@props([
    'backRoute'   => null,
    'submitLabel' => 'Simpan',
    'submitId'    => null,
])

<div class="d-flex justify-content-between align-items-center">
    @if($backRoute)
    <a href="{{ $backRoute }}" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali
    </a>
    @else
    <div></div>
    @endif
    <button type="submit" class="btn btn-primary" @if($submitId) id="{{ $submitId }}" @endif>
        <i class="fa-solid fa-floppy-disk me-1"></i> {{ $submitLabel }}
    </button>
</div>
