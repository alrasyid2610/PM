let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_testing_point",
        renderForm: renderForm,
        initSelect: function () {},
        initDynamicTable: true,
        useAttachment: true,
        historyConfig: {
            masterLabel: "Testing Point",
            linesLabel: "Testing Items",
            linesDisplayFields: ["nomor", "judul_indonesia", "judul_inggris", "nilai"],
        },
    });

    // setDynamicFormState(false);
});
