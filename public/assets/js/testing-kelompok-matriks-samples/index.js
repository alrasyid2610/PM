let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_testing_kelompok_matriks_sample",
        renderForm: renderParameterForm,
        initSelect: function () {
            $("#detail_kelompok").select2({
                width: "100%",
                dropdownParent: $("#detailContent"),
            });
        },

        // useAttachment: true,
    });
});
