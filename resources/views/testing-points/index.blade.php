@extends('layouts.app')

@section('page-title', 'Testing Points')
@section('page-descrip', 'Kelola data Testing Points pengujian laboratorium')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Testing Points</li>
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
<x-crud-index title="List of Testing Points" create-route="testing-points.create" :with-history="true" />
@endsection

@section('custom-script')
<script type="text/template" id="row-template">

    <tr>

        <input type="hidden" name="id_testing_item[]" value="">
        
        <td class="row-number"></td>

        <td>
            <input type="text" name="judul_indonesia[]" class="form-control">
        </td>

        <td>
            <input type="text" name="judul_inggris[]" class="form-control">
        </td>

        <td>
            <select name="parameter[]" class="form-control parameter-select"></select>
        </td>

        <td>
            <select name="unit[]" class="form-control unit-select"></select>
        </td>

        <td>
            <input type="text" name="nilai[]" class="form-control">
        </td>

        <td>
            <input type="text" name="keterangan[]" class="form-control">
        </td>

        <td class="text-center">
            <input type="checkbox" name="status[]" value="1">
        </td>

        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm btn-remove">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>

    </tr>

</script>




<script>
    window.route = {
        data: "{{ route('testing-points.data') }}",
        update: "{{ url('testing-points') }}/",
        deleteAttachment: "{{ route('testing-points.delete-attachment') }}",
        history: "{{ url('testing-points') }}/",
        csrf: "{{ csrf_token() }}"
    }
</script>

<script src="{{ asset('assets/js/testing-points/form.js') }}"></script>
<script src="{{ asset('assets/js/testing-points/index.js') }}"></script>
@endsection