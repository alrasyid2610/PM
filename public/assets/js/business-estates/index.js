let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_bestate",
        renderForm: renderForm,
        initSelect: function () {
            WilayahEngine.init("#detailContent");
        },
    });
});
