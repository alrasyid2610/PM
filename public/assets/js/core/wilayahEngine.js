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

    populate($el, data, selectedValue = "") {
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

    // Tandai select sudah di-populate agar tidak fetch ulang
    _markLoaded($el) {
        $el.attr("data-wilayah-loaded", "1");
    },
    _isLoaded($el) {
        return $el.attr("data-wilayah-loaded") === "1";
    },

    async init(container = "#detailContent") {
        const self = this;
        const s2 = { width: "100%", dropdownParent: $(container) };

        const $provinsi  = $(`${container} .wilayah-provinsi`);
        const $kota      = $(`${container} .wilayah-kota`);
        const $kecamatan = $(`${container} .wilayah-kecamatan`);
        const $kelurahan = $(`${container} .wilayah-kelurahan`);

        if (!$provinsi.hasClass("select2-hidden-accessible"))  $provinsi.select2(s2);
        if (!$kota.hasClass("select2-hidden-accessible"))      $kota.select2(s2);
        if (!$kecamatan.hasClass("select2-hidden-accessible")) $kecamatan.select2(s2);
        if (!$kelurahan.hasClass("select2-hidden-accessible")) $kelurahan.select2(s2);

        // Jika ada pre-selected value, load chain secara sequential (untuk edit mode)
        const selProvinsi = $provinsi.data("value");
        if (selProvinsi) {
            const provinsiData = await self.fetchProvinces();
            self.populate($provinsi, provinsiData, selProvinsi);
            self._markLoaded($provinsi);

            const provCode = self.getCodeByName(provinsiData, selProvinsi);
            if (provCode) {
                const kotaData = await self.fetchChildren(provCode);
                const selKota  = $kota.data("value");
                self.populate($kota, kotaData, selKota);
                self._markLoaded($kota);

                if (selKota) {
                    const kotaCode = self.getCodeByName(kotaData, selKota);
                    if (kotaCode) {
                        const kecData = await self.fetchChildren(kotaCode);
                        const selKec  = $kecamatan.data("value");
                        self.populate($kecamatan, kecData, selKec);
                        self._markLoaded($kecamatan);

                        if (selKec) {
                            const kecCode = self.getCodeByName(kecData, selKec);
                            if (kecCode) {
                                const kelData = await self.fetchChildren(kecCode);
                                const selKel  = $kelurahan.data("value");
                                self.populate($kelurahan, kelData, selKel);
                                self._markLoaded($kelurahan);
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

        function lazyBind($el, fetchFn) {
            $el.off("select2:opening.wilayah").on("select2:opening.wilayah", function (e) {
                if (self._isLoaded($el)) return;
                e.preventDefault();
                $("#global-loader").fadeIn(150);
                fetchFn().then(function (data) {
                    const sel = $el.data("value") || $el.val();
                    self.populate($el, data, sel);
                    self._markLoaded($el);
                    $("#global-loader").fadeOut(200);
                    $el.select2("open");
                }).catch(function () {
                    $("#global-loader").fadeOut(200);
                });
            });
        }

        lazyBind($provinsi, () => self.fetchProvinces());
        lazyBind($kota, () => {
            const code = $provinsi.find(":selected").attr("data-code");
            return code ? self.fetchChildren(code) : Promise.resolve([]);
        });
        lazyBind($kecamatan, () => {
            const code = $kota.find(":selected").attr("data-code");
            return code ? self.fetchChildren(code) : Promise.resolve([]);
        });
        lazyBind($kelurahan, () => {
            const code = $kecamatan.find(":selected").attr("data-code");
            return code ? self.fetchChildren(code) : Promise.resolve([]);
        });

        // Reset downstream saat pilihan berubah
        $provinsi.off("change.wilayah").on("change.wilayah", function () {
            $kota.empty().append('<option value="">-- Pilih --</option>').trigger("change");
            $kecamatan.empty().append('<option value="">-- Pilih --</option>').trigger("change");
            $kelurahan.empty().append('<option value="">-- Pilih --</option>').trigger("change");
            $kota.removeAttr("data-wilayah-loaded");
            $kecamatan.removeAttr("data-wilayah-loaded");
            $kelurahan.removeAttr("data-wilayah-loaded");
        });

        $kota.off("change.wilayah").on("change.wilayah", function () {
            $kecamatan.empty().append('<option value="">-- Pilih --</option>').trigger("change");
            $kelurahan.empty().append('<option value="">-- Pilih --</option>').trigger("change");
            $kecamatan.removeAttr("data-wilayah-loaded");
            $kelurahan.removeAttr("data-wilayah-loaded");
        });

        $kecamatan.off("change.wilayah").on("change.wilayah", function () {
            $kelurahan.empty().append('<option value="">-- Pilih --</option>').trigger("change");
            $kelurahan.removeAttr("data-wilayah-loaded");
        });
    },
};
