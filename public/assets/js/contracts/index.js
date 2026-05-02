let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_contract",
        renderForm: renderForm,
        useAttachment: true,
    });
});
