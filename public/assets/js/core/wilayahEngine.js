const WilayahEngine = {
    cache: {},

    fetch(url) {
        if (this.cache[url]) return Promise.resolve(this.cache[url]);
        return $.getJSON(url).then((data) => {
            this.cache[url] = data;
            return data;
        });
    },

    fetchProvinces() {
        return this.fetch("/wilayah/provinces");
    },

    fetchChildren(kode) {
        return this.fetch("/wilayah/children?kode=" + encodeURIComponent(kode));
    },

    populate(selector, data, selectedValue = "") {
        let $el = $(selector);
        $el.empty();
        $el.append(`<option value="">-- Pilih --</option>`);
        data.forEach((item) => {
            $el.append(
                `<option value="${item.name}" data-code="${item.id}" ${item.name === selectedValue ? "selected" : ""}>${item.name}</option>`
            );
        });
        $el.trigger("change");
    },

    reset(...selectors) {
        selectors.forEach((sel) => {
            $(sel).empty().append(`<option value="">-- Pilih --</option>`).trigger("change");
        });
    },

    getCodeByName(data, name) {
        let found = data.find((item) => item.name === name);
        return found ? found.id : null;
    },

    async init(container = "#detailContent") {
        const self = this;
        const s2 = { width: "100%", dropdownParent: $(container) };

        const $provinsi   = $(`${container} .wilayah-provinsi`);
        const $kota       = $(`${container} .wilayah-kota`);
        const $kecamatan  = $(`${container} .wilayah-kecamatan`);
        const $kelurahan  = $(`${container} .wilayah-kelurahan`);

        // Init select2 hanya jika belum di-init
        if (!$provinsi.hasClass('select2-hidden-accessible'))  $provinsi.select2(s2);
        if (!$kota.hasClass('select2-hidden-accessible'))      $kota.select2(s2);
        if (!$kecamatan.hasClass('select2-hidden-accessible')) $kecamatan.select2(s2);
        if (!$kelurahan.hasClass('select2-hidden-accessible')) $kelurahan.select2(s2);

        let provinsiData = await self.fetchProvinces();
        let selProvinsi  = $provinsi.data("value");
        self.populate($provinsi, provinsiData, selProvinsi);

        if (selProvinsi) {
            let provCode = self.getCodeByName(provinsiData, selProvinsi);
            if (provCode) {
                let kotaData = await self.fetchChildren(provCode);
                let selKota  = $kota.data("value");
                self.populate($kota, kotaData, selKota);

                if (selKota) {
                    let kotaCode = self.getCodeByName(kotaData, selKota);
                    if (kotaCode) {
                        let kecData = await self.fetchChildren(kotaCode);
                        let selKec  = $kecamatan.data("value");
                        self.populate($kecamatan, kecData, selKec);

                        if (selKec) {
                            let kecCode = self.getCodeByName(kecData, selKec);
                            if (kecCode) {
                                let kelData = await self.fetchChildren(kecCode);
                                let selKel  = $kelurahan.data("value");
                                self.populate($kelurahan, kelData, selKel);
                            }
                        }
                    }
                }
            }
        }

        self.bindEvents($provinsi, $kota, $kecamatan, $kelurahan);
    },

    bindEvents($provinsi, $kota, $kecamatan, $kelurahan) {
        const self = this;

        // Gunakan direct binding (bukan delegated) agar select2 change terpicu dengan benar
        $provinsi.off("change.wilayah").on("change.wilayah", async function () {
            const code = $(this).find(":selected").attr("data-code");
            $kota.empty().append('<option value="">-- Pilih --</option>').trigger("change");
            $kecamatan.empty().append('<option value="">-- Pilih --</option>').trigger("change");
            $kelurahan.empty().append('<option value="">-- Pilih --</option>').trigger("change");
            if (!code) return;
            const data = await self.fetchChildren(code);
            self.populate($kota, data);
        });

        $kota.off("change.wilayah").on("change.wilayah", async function () {
            const code = $(this).find(":selected").attr("data-code");
            $kecamatan.empty().append('<option value="">-- Pilih --</option>').trigger("change");
            $kelurahan.empty().append('<option value="">-- Pilih --</option>').trigger("change");
            if (!code) return;
            const data = await self.fetchChildren(code);
            self.populate($kecamatan, data);
        });

        $kecamatan.off("change.wilayah").on("change.wilayah", async function () {
            const code = $(this).find(":selected").attr("data-code");
            $kelurahan.empty().append('<option value="">-- Pilih --</option>').trigger("change");
            if (!code) return;
            const data = await self.fetchChildren(code);
            self.populate($kelurahan, data);
        });
    },
};
