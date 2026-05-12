@extends('layouts.app')
@section('content')
<x-crud-index
    title="List of Work Orders"
    create-route="work-orders.create"
    :with-history="true"
/>
@endsection


@section('custom-script')
<script>
    window.route = {
        data: "{{ route('work-orders.data') }}",
        history: "{{ url('work-orders') }}/",
        csrf: "{{ csrf_token() }}",
        update: "{{ url('work-orders') }}/",
        boqProgress: "{{ url('work-orders') }}/",
    }
</script>
<script src="{{ asset('assets/js/work-order/index.js') }}"></script>
<script src="{{ asset('assets/js/work-order/form.js') }}"></script>
<script src="{{ asset('assets/js/work-order/period.js') }}"></script>
@endsection