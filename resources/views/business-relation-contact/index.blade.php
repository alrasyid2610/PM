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
<style>
    thead * {
        text-align: center
    }
</style>

<section class="section">
    <div class="card">
        <div class="card-body">

            <x-datatable-header
                title="List of Business Relation Contacts"
                create-route="business-relation-contacts.create"
                add-label="Add Data"
                :with-history="true"
            />
            
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-data">
                    <div class="table-responsive">
                        <table id="business-relation-contacts-table" class="table table-striped table-hover table-sm table-bordered w-100" data-datatable-auto-columns="true">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-detail">
                    <div id="detailContent" class="p-3 text-muted">Pilih data pada tab Data untuk melihat detail</div>
                </div>
                <div class="tab-pane fade" id="tab-history">
                    <div id="historyContent" class="p-3 text-muted">
                        Pilih data pada tab Data untuk melihat history
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('custom-script')
<script>
    window.route = {
        data: "{{ route('business-relation-contacts.data') }}",
        update: "{{ url('business-relation-contacts') }}/",
        csrf: "{{ csrf_token() }}",
        history: "{{ url('business-relation-contacts') }}/",
    }
</script>
<script src="{{ asset('assets/js/business-relation-contact/index.js') }}"></script>
<script src="{{ asset('assets/js/business-relation-contact/form.js') }}"></script>
@endsection