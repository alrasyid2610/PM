@extends('layouts.app')

@section('page-title', 'Business Relation Contacts')
@section('page-descrip', 'Kelola data Business Relation Contacts')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Business Relation Contacts</li>
    {{-- <li class="breadcrumb-item" aria-current="page">
          <a href="{{ route('testing-units.index') }}">Testing Units</a>
    </li> --}}
@endsection

@section('page-icon')
    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M28 8h4v28l-16 28h48L48 36V8h4" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M28 8h24" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <circle cx="32" cy="56" r="3" fill="white"/>
        <circle cx="44" cy="62" r="2" fill="white"/>
        <circle cx="38" cy="52" r="2" fill="white"/>
    </svg>
@endsection

@section('content')
<x-crud-index
    title="List of Business Relation Contacts"
    create-route="business-relation-contacts.create"
    :with-history="true"
/>
@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('business-relation-contacts.data') }}",
        update: "{{ url('business-relation-contacts') }}/",
        csrf: "{{ csrf_token() }}",
        history: "{{ url('business-relation-contacts') }}/",
    }

    window.datatableHeaderLabels = {
        is_aktif: 'Status',
    };

    window.datatableColumnRenderers = {
        is_aktif: function (data) {
            return data == 1
                ? '<span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:11px;font-weight:600;">Aktif</span>'
                : '<span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:600;">Tidak Aktif</span>';
        }
    };
</script>
<script src="{{ asset('assets/js/business-relation-contact/index.js') }}"></script>
<script src="{{ asset('assets/js/business-relation-contact/form.js') }}"></script>
@endsection