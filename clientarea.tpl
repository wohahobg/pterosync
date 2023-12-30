<div class="text-center">
    <p style="padding: 0 0 2em;">
        <a href="{$serviceurl}" target="_blank" class="btn btn-default">{$LANG.GOTO_PANEL}</a>
    </p>
    <h5 class="text-center" style="padding: 0 0 1em;">{$serverIp}</h5>
    <h4 class="text-center" style="padding: 0 0 1em;">{$LANG.QUICK_ACTIONS_PANEL}</h4>
    <!-- Your HTML Buttons using Smarty variables -->
    <button id="startButton"
            onclick="if (confirm('Are you sure you want to start this server?')) sendRequest('{$starturl}')"
            class="btn btn-success" style="margin-bottom: 0.3em;" {if $current_state === 'online'} disabled{/if}><i
                class="fas fa-play"></i> {$LANG.SERVER_START_PANEL}</button>
    <button id="rebootButton"
            onclick="if (confirm('Are you sure you want to reboot this server?')) sendRequest('{$rebooturl}')"
            class="btn btn-warning" style="margin-bottom: 0.3em;" {if $current_state === 'offline'} disabled{/if}><i
                class="fas fa-sync"></i> {$LANG.SERVER_RESTART_PANEL}</button>
    <button id="stopButton"
            onclick="if (confirm('Are you sure you want to stop this server?')) sendRequest('{$stopurl}')"
            class="btn btn-danger" style="margin-bottom: 0.3em;" {if $current_state === 'offline'} disabled{/if}><i
                class="fas fa-stop"></i> {$LANG.SERVER_STOP_PANEL}</button>

</div>

<script>
    let currentState = "{$current_state}";
    const serverStateUrl = "{$getstateurl}"
</script>
{literal}
    <script>
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


        });
    </script>
{/literal}
