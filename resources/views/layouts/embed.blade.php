<!DOCTYPE html>
<html lang="en">
@include('layouts.header')

<body style="background:#f7faff;">

<div class="container-fluid py-3 px-4">
    @yield('content')
</div>

<div id="global-loader">
    <div class="gl-content">
        <img src="{{ asset('assets/images/logo.png') }}" alt="Pramatek" class="gl-logo">
        <div class="gl-bar"><div class="gl-bar-fill"></div></div>
    </div>
</div>

<div id="scientific-toolbar">
    <div class="toolbar-header">Scientific Symbols</div>
    <div class="toolbar-group">
        <span data-symbol="²">²</span><span data-symbol="³">³</span>
        <span data-symbol="µ">µ</span><span data-symbol="°C">°C</span>
        <span data-symbol="CO₂">CO₂</span><span data-symbol="H₂O">H₂O</span>
        <span data-symbol="m²">m²</span><span data-symbol="m³">m³</span>
        <span data-symbol="mg/L">mg/L</span><span data-symbol="ppm">ppm</span>
    </div>
</div>

<!-- Scripts -->
<script src="{{ asset('assets/vendor/jquery/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/vendor/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('assets/vendor/datatables/dataTables.min.js') }}"></script>
<script src="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('assets/js/notification.js') }}"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>
<script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>
<script src="{{ asset('assets/js/core/wilayahEngine.js') }}"></script>
<script src="{{ asset('assets/js/core/formSubmitEngine.js') }}"></script>
<script src="{{ asset('assets/js/core/createFormHandler.js') }}"></script>
<script src="{{ asset('assets/js/core/attachmentEngine.js') }}"></script>
<script src="{{ asset('assets/js/core/formComponents.js') }}"></script>
<script src="{{ asset('assets/js/core/permissionEngine.js') }}"></script>
<script src="{{ asset('assets/js/pm.js') }}"></script>
@auth
<script>
    window.userPermissions = @json(getUserPermissions(auth()->id()));
    window.currentMenuSlug = '{{ request()->segment(1) }}';
</script>
@endauth
<script src="{{ asset('assets/js/scientific-input.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/cleave.js@1.6.0/dist/cleave.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
<script>
    function initNumericMask(container) {
        $(container).find('.input-num-mask').each(function () {
            if (this._cleave) return;
            const isInt = $(this).hasClass('input-num-int');
            this._cleave = new Cleave(this, {
                numeral: true,
                numeralThousandsGroupStyle: 'thousand',
                delimiter: ',',
                numeralDecimalMark: '.',
                numeralDecimalScale: isInt ? 0 : 2,
            });
        });
    }
    function rawNumVal(el) {
        if (!el) return null;
        const raw = el._cleave ? el._cleave.getRawValue() : String($(el).val()).replace(/,/g, '');
        const n = parseFloat(raw);
        return isNaN(n) ? null : n;
    }
    function initFpDate(container) {
        $(container).find('.fp-date').each(function () {
            if (this._fp) return;
            const name = this.name;
            const isSelesai = name && name.toLowerCase().includes('selesai');
            const fp = flatpickr(this, {
                locale: 'id',
                dateFormat: 'Y-m-d',
                allowInput: false,
                defaultDate: $(this).val() || null,
                onChange: !isSelesai ? function(selectedDates, dateStr) {
                    const $form = $(container).find('.fp-date[name*="selesai"]');
                    if ($form.length && $form[0]._fp) {
                        $form[0]._fp.set('minDate', dateStr || null);
                        if ($form[0]._fp.selectedDates[0] && $form[0]._fp.selectedDates[0] < selectedDates[0]) {
                            $form[0]._fp.clear();
                        }
                    }
                } : undefined,
            });
            if (isSelesai) {
                const mulaiVal = $(container).find('.fp-date[name*="mulai"]').val();
                if (mulaiVal) fp.set('minDate', mulaiVal);
            }
            this._fp = fp;
        });
        $(container).find('.fp-datetime').each(function () {
            if (this._fp) return;
            this._fp = flatpickr(this, {
                locale: 'id',
                dateFormat: 'Y-m-d H:i',
                enableTime: true,
                time_24hr: true,
                allowInput: false,
                defaultDate: $(this).val() || null,
            });
        });
    }
</script>
@yield('custom-script')

</body>
</html>
