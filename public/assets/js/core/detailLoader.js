function loadDetailEngine(options) {
    const id = options.id;
    const url = options.url;
    const container = options.container;
    const render = options.render;
    const afterRender = options.afterRender;

    $(container).html("Loading...");
    window.dataBefore = [];

    $.get(url + id, async function (res) {
        window.dataBefore.push(res);
        const html = await Promise.resolve(render(res));
        $(container).html(html);
        if (afterRender) {
            afterRender(res);
        }
    }).fail(function () {
        $(container).html('');
        Notify.error('Data tidak ditemukan atau sudah dihapus');
        window.history.replaceState(null, '', window.location.pathname);
        $('button[data-bs-target="#tab-data"]').trigger('click');
    });
}
