function loadDetailEngine(options) {
    const id = options.id;
    const url = options.url;
    const container = options.container;
    const render = options.render;
    const afterRender = options.afterRender;

    $("#global-loader").fadeIn(150);
    window.dataBefore = [];

    $.get(url + id, async function (res) {
        window.dataBefore.push(res);
        const html = await Promise.resolve(render(res));
        $(container).html(html);
        if (afterRender) {
            afterRender(res);
        }
        $("#global-loader").fadeOut(300);
    }).fail(function (xhr) {
        $("#global-loader").fadeOut(300);
        $(container).html('');
        if (xhr && xhr.status === 403) {
            Notify.error((xhr.responseJSON && xhr.responseJSON.message) || 'Anda tidak memiliki akses ke data ini');
        } else {
            Notify.error('Data tidak ditemukan atau sudah dihapus');
        }
        window.history.replaceState(null, '', window.location.pathname);
        $('button[data-bs-target="#tab-data"]').trigger('click');
    });
}
