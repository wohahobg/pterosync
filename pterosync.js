$(document).ajaxSuccess(function (e, jqxhr, settings) {
    if (typeof settings.data === 'string') {
        var data = settings.data.split('&').reduce(function (obj, param) {
            var parts = param.split('=');
            obj[decodeURIComponent(parts[0])] = decodeURIComponent(parts[1]);
            return obj;
        }, {});

        // Check if the 'module' key is 'pterosync'
        if (data.module === 'pterosync') {
            if (settings.url.includes('configproducts.php')){
                loadAdminConfigProducts();
            }
        }
    }
});

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
    if (!eggElement.find('option:selected').length) {
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

function loadAdminConfigProducts() {
    const nestElement = loadSelect2('cNestId', true);
    const eggElement = loadSelect2('cEggId', false);
    let selectedValue = nestElement.find('option:selected').val();
    filterEggs(eggElement, selectedValue)
    nestElement.on('change', function () {
        selectedValue = $(this).val();
        filterEggs(eggElement, selectedValue)
    })
}

function disableButtons(state) {
    var startButton = $('#startButton');
    var rebootButton = $('#rebootButton');
    var stopButton = $('#stopButton');
    currentState = state;
    switch (state) {
        case "running":
            startButton.prop('disabled', true);
            rebootButton.prop('disabled', false);
            stopButton.prop('disabled', false);
            break;
        case "offline":
            startButton.prop('disabled', false);
            rebootButton.prop('disabled', true);
            stopButton.prop('disabled', true);
            break;
        case "starting":
        case "stopping":
            startButton.prop('disabled', true);
            rebootButton.prop('disabled', true);
            stopButton.prop('disabled', true);
            break;
        default:
            startButton.prop('disabled', true);
            rebootButton.prop('disabled', true);
            stopButton.prop('disabled', true);
            break;
    }
}


function sendRequest(url) {
    disableButtons('request')
    var $button = $(event.currentTarget);
    var originalHtml = $button.html();
    $button.html('<i class="fas fa-spinner fa-spin"></i> ' + originalHtml);

    $.post(url, function (data, status) {
        disableButtons(data.state)
        const interval = setInterval(function () {
            $.post(serverStateUrl, function (data, status) {
                if (data.state === 'running') {
                    $button.html(originalHtml); // Restore original content after completion
                    clearInterval(interval)
                }
                disableButtons(data.state)
            })
        }, 2000);
    });
}

$(document).ready(function () {
    disableButtons(currentState)
   // $('[href="#tabChangepw"]').click()
    $(document).on('click', '.copy-text', function () {
        // Get the text from data-text attribute
        const text = $(this).data('clipboard-text');
        if (!text) alert('clipboard-text missing')
        navigator.clipboard.writeText(text);

        const changeTextId = $(this).data('id')
        if (!changeTextId) alert('data-id missing')
        const newText = $(this).data('new-text')
        if (!newText) alert('new-text missing')
        const findElement = $(`#${changeTextId}`)
        if (!findElement.length) alert(`element ${findElement} not found!`)

        let timer = 2000;
        if ($(this).data('timer')) {
            timer = $(this).data('timer');
        }

        // Check if the element is an input field
        if (findElement.is('input')) {
            // It's an input field, change the value
            const originalValue = findElement.val();
            findElement.val(newText);
            setTimeout(function () {
                findElement.val(originalValue);
            }, timer);
        } else {
            // It's not an input field, change the HTML content
            const originalContent = findElement.html();
            findElement.html(newText);
            setTimeout(function () {
                findElement.html(originalContent);
            }, timer);
        }
    })
});

