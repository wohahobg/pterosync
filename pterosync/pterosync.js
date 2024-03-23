$(document).ajaxSuccess(function (e, jqxhr, settings) {
    if (typeof settings.data === 'string') {
        var data = settings.data.split('&').reduce(function (obj, param) {
            var parts = param.split('=');
            obj[decodeURIComponent(parts[0])] = decodeURIComponent(parts[1]);
            return obj;
        }, {});

        // Check if the 'module' key is 'pterosync'
        if (data.module === 'pterosync') {
            if (settings.url.includes('configproducts.php')) {
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

function disableButtons(state, isStable = false) {
    var startButton = $('#startButton');
    var rebootButton = $('#rebootButton');
    var stopButton = $('#stopButton');
    var killButton = $('#killButton');

    switch (state) {
        case "running":
            startButton.prop('disabled', true);
            rebootButton.prop('disabled', !isStable);
            stopButton.prop('disabled', !isStable);
            killButton.hide();
            setGameServerStatus();
            break;
        case "offline":
            startButton.prop('disabled', false);
            rebootButton.prop('disabled', true);
            stopButton.prop('disabled', true);
            killButton.hide();
            setGameServerStatus();
            break;
        case "starting":
        case "stopping":
            startButton.prop('disabled', true);
            rebootButton.prop('disabled', true);
            stopButton.prop('disabled', true);
            if (state === "stopping") {
                killButton.show();
                stopButton.hide();
            }
            break;
        default:
            startButton.prop('disabled', true);
            rebootButton.prop('disabled', true);
            stopButton.prop('disabled', true);
            killButton.hide();
            break;
    }
}


function sendRequest(url, type) {

    var $button = $(event.currentTarget);
    var originalHtml = $button.html();
    $button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

    let stateSince = null;
    let stabilizationPeriod = 8000;
    if (type === 'start') {
        stabilizationPeriod = 1;
    }

    $.post(url, function (data) {
        disableButtons(data.state);

        const interval = setInterval(function () {
            $.post(serverStateUrl, function (data) {
                if ((type === 'kill' || type === 'stop') && data.state === 'offline') {
                    clearInterval(interval);
                    $button.html(originalHtml).prop('disabled', false);
                    disableButtons(data.state, true);
                    return;
                }
                if ((type === 'start' || type === 'restart') && data.state === 'running') {
                    if (!stateSince) {
                        stateSince = Date.now();
                    } else if (Date.now() - stateSince > stabilizationPeriod) {
                        clearInterval(interval);
                        $button.html(originalHtml).prop('disabled', false);
                        disableButtons(data.state, true);
                    }
                } else if (data.state !== 'running') {
                    stateSince = null;
                }
            });
        }, 3000);
    }).fail(function () {
        $button.html(originalHtml).prop('disabled', false);
        alert('Request failed. Please try again.');
    });
}

function setGameServerStatus() {
    if (typeof serverQueryData !== 'undefined') {
        const {game, address} = serverQueryData;

        const apiUrl = `https://api.gamecms.org/game/${game}/${address}`;
        fetch(apiUrl)
            .then(response => {
                if (response.status === 200) {
                    return response.json();
                } else {
                    throw new Error('Server is offline');
                }
            })
            .then(data => {
                document.getElementById('game-server-status').innerHTML = `
                <ul class="list-group list-group-flush">
                    ${data.server_name !== '' ? `<li class="list-group-item">Server Name: <strong>${data.server_name}</strong></li>` : ''}
                    <li class="list-group-item">Status: <span class="text-success"><i class="fas fa-circle"></i> Online</span></li>
                    <li class="list-group-item">Players: <strong>${data.players.online}/${data.players.max}</strong></li>
                </ul>
                `;

            })
            .catch(error => {
                console.log('PteroSync Server Status Error', error);
                document.getElementById('game-server-status').innerHTML = `
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Status: <span class="text-danger"><i class="fas fa-circle"></i> Offline</span></li>
                </ul>
            `;
            });
    }
}

$(document).ready(function () {

    if (typeof currentState === 'undefined') return;

    disableButtons(currentState, true)

    $(document).on('click', '.copy-text', function () {
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

        if (findElement.is('input')) {
            const originalValue = findElement.val();
            findElement.val(newText);
            setTimeout(function () {
                findElement.val(originalValue);
            }, timer);
        } else {
            const originalContent = findElement.html();
            findElement.html(newText);
            setTimeout(function () {
                findElement.html(originalContent);
            }, timer);
        }
    })
});

