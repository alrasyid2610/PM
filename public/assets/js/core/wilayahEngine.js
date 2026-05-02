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

        $(`${container} .wilayah-provinsi`).select2(s2);
        $(`${container} .wilayah-kota`).select2(s2);
        $(`${container} .wilayah-kecamatan`).select2(s2);
        $(`${container} .wilayah-kelurahan`).select2(s2);

        let provinsiData = await self.fetchProvinces();
        let selProvinsi = $(`${container} .wilayah-provinsi`).data("value");
        self.populate(`${container} .wilayah-provinsi`, provinsiData, selProvinsi);

        if (selProvinsi) {
            let provCode = self.getCodeByName(provinsiData, selProvinsi);
            if (provCode) {
                let kotaData = await self.fetchChildren(provCode);
                let selKota = $(`${container} .wilayah-kota`).data("value");
                self.populate(`${container} .wilayah-kota`, kotaData, selKota);

                if (selKota) {
                    let kotaCode = self.getCodeByName(kotaData, selKota);
                    if (kotaCode) {
                        let kecData = await self.fetchChildren(kotaCode);
                        let selKec = $(`${container} .wilayah-kecamatan`).data("value");
                        self.populate(`${container} .wilayah-kecamatan`, kecData, selKec);

                        if (selKec) {
                            let kecCode = self.getCodeByName(kecData, selKec);
                            if (kecCode) {
                                let kelData = await self.fetchChildren(kecCode);
                                let selKel = $(`${container} .wilayah-kelurahan`).data("value");
                                self.populate(`${container} .wilayah-kelurahan`, kelData, selKel);
                            }
                        }
                    }
                }
            }
        }

        self.bindEvents(container);
    },

    bindEvents(container = "#detailContent") {
        const self = this;

        $(document)
            .off("change", `${container} .wilayah-provinsi`)
            .on("change", `${container} .wilayah-provinsi`, async function () {
                let code = $(this).find(":selected").data("code");
                self.reset(`${container} .wilayah-kota`, `${container} .wilayah-kecamatan`, `${container} .wilayah-kelurahan`);
                if (!code) return;
                let data = await self.fetchChildren(code);
                self.populate(`${container} .wilayah-kota`, data);
            });

        $(document)
            .off("change", `${container} .wilayah-kota`)
            .on("change", `${container} .wilayah-kota`, async function () {
                let code = $(this).find(":selected").data("code");
                self.reset(`${container} .wilayah-kecamatan`, `${container} .wilayah-kelurahan`);
                if (!code) return;
                let data = await self.fetchChildren(code);
                self.populate(`${container} .wilayah-kecamatan`, data);
            });

        $(document)
            .off("change", `${container} .wilayah-kecamatan`)
            .on("change", `${container} .wilayah-kecamatan`, async function () {
                let code = $(this).find(":selected").data("code");
                self.reset(`${container} .wilayah-kelurahan`);
                if (!code) return;
                let data = await self.fetchChildren(code);
                self.populate(`${container} .wilayah-kelurahan`, data);
            });
    },
};
