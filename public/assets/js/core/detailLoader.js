function loadDetailEngine(options) {
    const id = options.id;
    const url = options.url;
    const container = options.container;
    const render = options.render;
    const afterRender = options.afterRender;

    $(container).html("Loading...");
    console.log("loading.....");
    window.dataBefore = [];
    console.log("data before : ", window.dataBefore);

    $.get(url + id, function (res) {
        window.dataBefore.push(res);
        const html = render(res);
        $(container).html(html);
        if (afterRender) {
            afterRender(res);

            // setTimeout(() => {
            //     window.initialForms = getAllFormsData();
            //     window.initialItems = getDynamicTableData();
            // }, 3000);
        }
    }).fail(function () {
        $(container).html("Gagal memuat detail");
    });
}
