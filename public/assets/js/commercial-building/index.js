let page;

$(document).ready(function () {
    page = new CrudPageController({
        primaryKey: "id_building",
        renderForm: renderUnitForm,
        initSelect: function () {
            WilayahEngine.init("#detailContent");
        },
    });
});
