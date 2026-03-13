(function () {
    const superscript = {
        0: "⁰",
        1: "¹",
        2: "²",
        3: "³",
        4: "⁴",
        5: "⁵",
        6: "⁶",
        7: "⁷",
        8: "⁸",
        9: "⁹",
    };

    const subscript = {
        0: "₀",
        1: "₁",
        2: "₂",
        3: "₃",
        4: "₄",
        5: "₅",
        6: "₆",
        7: "₇",
        8: "₈",
        9: "₉",
    };

    const chemicalRules = {
        CO2: "CO₂",
        CO: "CO",

        SO24: "SO₄²-",

        SO2: "SO₂",
        SO3: "SO₃",

        NO: "NO",
        NO2: "NO₂",
        N2O: "N₂O",

        O2: "O₂",
        O3: "O₃",

        H2O: "H₂O",
        H2O2: "H₂O₂",

        NH3: "NH₃",

        CH4: "CH₄",
        C2H6: "C₂H₆",

        HCl: "HCl",
        HF: "HF",

        Cl2: "Cl₂",

        H2S: "H₂S",

        SO4: "SO₄",
        NO3: "NO₃",
        PO4: "PO₄",

        CaCO3: "CaCO₃",
        NaCl: "NaCl",

        KCl: "KCl",

        MgSO4: "MgSO₄",

        HNO3: "HNO₃",
        H2SO4: "H₂SO₄",

        NaOH: "NaOH",
        KOH: "KOH",
    };

    const unitRules = {
        m2: "m²",
        m3: "m³",

        degC: "°C",
        um: "µm",

        ug: "µg",

        "mg/L": "mg/L",
        "ug/m3": "µg/m³",
    };

    $(document).on("input", ".scientific-input", function () {
        let val = $(this).val();

        for (let key in chemicalRules) {
            let regex = new RegExp(key, "gi");

            val = val.replace(regex, chemicalRules[key]);
        }

        for (let key in unitRules) {
            let regex = new RegExp(key, "gi");

            val = val.replace(regex, unitRules[key]);
        }

        $(this).val(val);
    });

    let activeInput = null;

    $(document).on("focus", ".scientific-input", function () {
        activeInput = this;

        let offset = $(this).offset();

        $("#scientific-toolbar")
            .css({
                top: offset.top + $(this).outerHeight() + 6,
                left: offset.left,
            })
            .show();
    });

    $(document).on("click", "#scientific-toolbar span", function () {
        let symbol = $(this).data("symbol");

        if (activeInput) {
            activeInput.value += symbol;

            activeInput.focus();
        }
    });

    $(document).click(function (e) {
        if (
            !$(e.target).closest(".scientific-input,#scientific-toolbar").length
        ) {
            $("#scientific-toolbar").hide();
        }
    });
})();
