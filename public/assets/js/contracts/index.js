let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_contract",
        renderForm: renderForm,
        initSelect: function () {
            $("#detail_kelompok").select2({
                width: "100%",
                dropdownParent: $("#detailContent"),
            });
        },
        useAttachment: true,
    });
});
