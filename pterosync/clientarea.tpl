<div class="text-center">
    {if $serverFound == false}
        <p class="alert alert-warning">{$LANG.SERVER_NOT_FOUND}</p>
    {else}
        <p class="margin-top-bottom">
            <a href="{$serviceUrl}" target="_blank" class="btn btn-default">{$LANG.GOTO_PANEL}</a>
        </p>
        <div class="row mt-2 justify-content-center" id="game-server-status">

        </div>
        <div class="row mt-2 mb-5">
            <div class="col">
                <label for="change-text-id">
                    {$LANG.SERVER_ID}
                </label>
                <input
                        class="form-control text-center copy-text"
                        type="text"
                        id="change-text-id"
                        data-id="change-text-id"
                        data-new-text="{$LANG.ID_COPIED}"
                        data-clipboard-text="{$serverId}"
                        value="{$serverId}"
                        readonly>
            </div>
            <div class="col">
                <label for="change-text-ip">
                    {$LANG.SERVER_IP}
                </label>
                <input
                        class="form-control text-center copy-text"
                        id="change-text-ip"
                        data-id="change-text-ip"
                        data-new-text="{$LANG.IP_COPIED}"
                        data-clipboard-text="{$serverIp}"
                        value="{$serverIp}" readonly>
            </div>
        </div>
        <h2 class="mt-2">{$LANG.QUICK_ACTIONS_PANEL}</h2>
        <button id="startButton"
                onclick="if (confirm('{$LANG.SERVER_START_PANEL_CONFIRM_MESSAGE}')) sendRequest('{$startUrl}','start')"
                {if $current_state === 'online'} disabled{/if}
                class="btn btn-success mt-2"><i
                    class="fas fa-play"></i> {$LANG.SERVER_START_PANEL}</button>
        <button id="rebootButton"
                onclick="if (confirm('{$LANG.SERVER_RESTART_PANEL_CONFIRM_MESSAGE}')) sendRequest('{$rebootUrl}', 'restart')"
                {if $current_state === 'offline'} disabled{/if}
                class="btn btn-warning mt-2"><i
                    class="fas fa-sync"></i> {$LANG.SERVER_RESTART_PANEL}</button>
        <button id="stopButton"
                onclick="if (confirm('{$LANG.SERVER_STOP_PANEL_CONFIRM_MESSAGE}')) sendRequest('{$stopUrl}','stop')"
                {if $current_state === 'offline'} disabled{/if}
                class="btn btn-danger mt-2"><i
                    class="fas fa-stop"></i> {$LANG.SERVER_STOP_PANEL}</button>
        <button id="killButton"
                style="display: none;"
                {if $current_state === 'stopping'} disabled{/if}
                onclick="if (confirm('{$LANG.SERVER_KILL_PANEL_CONFIRM_MESSAGE}')) sendRequest('{$killUrl}','kill')"
                class="btn btn-danger mt-2"><i
                    class="fas fa-times"></i> {$LANG.SERVER_KILL_PANEL}</button>

        <button type="button" class="btn btn-info mt-2" data-toggle="modal" data-target="#ftpDetails">
            <i class="fas fa-file-upload"></i>
            {$LANG.FTP_DETAILS}
        </button>
        <!-- The modal -->
        <div class="modal fade" id="ftpDetails" tabindex="-1" role="dialog" aria-labelledby="modalLabel"
             aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modalLabel">
                            {$LANG.SERVER_FTP_INFORMATION}
                        </h4>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning" role="alert">
                            {$LANG.SERVER_FTP_PASSWORD_INFORMATION}
                        </div>
                        <!-- FTP Host + Port -->
                        <div class="mt-2">
                            <label for="ftpHost" class="form-label">
                                {$LANG.SERVER_FTP_HOST_AND_PORT}
                            </label>
                            <input type="text" class="form-control copy-text"
                                   data-id="ftpHost"
                                   data-new-text="{$LANG.SERVER_FTP_HOST_COPIED}"
                                   data-clipboard-text="{$ftpDetails['host']}"
                                   id="ftpHost"
                                   value="{$ftpDetails['host']}" readonly>
                        </div>
                        <!-- FTP Username -->
                        <div class="mt-2">
                            <label for="ftpUsername" class="form-label">
                                {$LANG.SERVER_FTP_USERNAME}
                            </label>
                            <input type="text" class="form-control copy-text"
                                   data-id="ftpUsername"
                                   data-new-text="{$LANG.SERVER_FTP_USERNAME_COPIED}"
                                   data-clipboard-text="{$ftpDetails['username']}"
                                   id="ftpUsername"
                                   value="{$ftpDetails['username']}" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">
                            {$LANG.close}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    {/if}
</div>
<script>
    let currentState = "{$currentState}";
    const serverStateUrl = "{$getStateUrl}"
</script>
{if $gameQueryData['game'] !== false }
    <script>
        let serverQueryData = {
            "game": "{$gameQueryData['game']}",
            "address": "{$gameQueryData['address']}:{$gameQueryData['port']}"
        }
    </script>
{/if}
