let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_testing_unit",
        renderForm: renderUnitForm,
        initSelect: function () {
            $("#detail_kelompok").select2({
                width: "100%",
                dropdownParent: $("#detailContent"),
            });
        },
    });
});
