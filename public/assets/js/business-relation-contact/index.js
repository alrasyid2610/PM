let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_contact",
        renderForm: renderForm,
        initSelect: function () {
            $("#detail_id_br").select2({
                width: "100%",
                dropdownParent: $("#detailContent"),
            });
        },
    });
});
