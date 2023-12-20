function loadScripts() {
    // URLs for the scripts
    const scriptUrls = [
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js'
    ];

    // Load Select2 after jQuery has loaded
    loadScript(scriptUrls[0], function () {
        var style = document.createElement('style');
        style.type = 'text/css';
        style.innerHTML = '.select-inline { max-width: 200px; }';
        document.getElementsByTagName('head')[0].appendChild(style);

        yourMainFunction();
    });
}

function loadScript(url, callback) {
    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = url;
    script.onload = callback;
    document.body.appendChild(script);
}

function loadSelect2(element, intSelect2) {
    const cNestIdElement = $(`#${element}`);
    if (cNestIdElement.length) {
        let tdParent = cNestIdElement.closest('td');
        const fieldareaElement = tdParent.next('td');
        if (fieldareaElement.length && fieldareaElement.hasClass('fieldarea')) {
            const selectElement = fieldareaElement.find('select');
            if (selectElement.length) {
                if (intSelect2) {
                    const selectName = selectElement.attr('name');
                    $(`[name="${selectName}"]`).select2();
                }
                return selectElement;
            }
        }
    }
    return false;
}

function filterEggs(eggElement, nestId) {
    eggElement.find('option').each(function () {
        const optionName = $(this).text();
        // Check if the optionValue contains the selectedValue in parentheses
        if (optionName.includes(`(${nestId})`)) {
            $(this).show(); // Show the option
        } else {
            $(this).attr('selected', false)
            $(this).hide(); // Hide the option if it doesn't match
        }
    });
    // Check if there's already a selected option within eggElement
    if (eggElement.find('option:selected').length === 0) {
        eggElement.find('option').each(function () {
            // Check if the option is visible
            if ($(this).css('display') !== 'none') {
                // Set the first visible option as selected
                $(this).prop("selected", true);
                return false; // Exit the loop after setting the first visible option
            }
        });
    }
}

function yourMainFunction() {
    const nestElement = loadSelect2('cNestId', true);
    const eggElement = loadSelect2('cEggId', false);
    let selectedValue = nestElement.find('option:selected').val();
    filterEggs(eggElement, selectedValue)
    nestElement.on('change', function () {
        selectedValue = $(this).val();
        filterEggs(eggElement, selectedValue)
    })
}

loadScripts();