let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_termin",
        renderForm: renderForm,
        initSelect: function () {
            $("#detail_status").select2({
                width: "100%",
                dropdownParent: $("#detailContent"),
            });
        },
        useAttachment: true,
    });
});
