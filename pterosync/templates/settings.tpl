<div class="text-center">
    <form method="post" action="{$saveSettingUrl}" id="saveSettings">
        <input type="hidden" name="token" value="{$csrfToken}">

        <div class="row">
            {foreach $editableVariables as $key => $variable}

            <div class="col-md-6">
                <div class="form-group">
                    <label for="{$variable.env_variable}">
                        {$variable.name}
                    </label>

                    {if $variable.rule == 'input'}
                        <input type="text" name="{$variable.env_variable}" id="{$variable.env_variable}"
                               class="form-control" value="{$environment[$variable.env_variable]}"
                               {if $variable.max_input}maxlength="{$variable.max_input}"{/if}
                                {if $variable.required}required{/if}>
                    {elseif $variable.rule == 'select' && $variable.options|@count > 0}
                        <select name="{$variable.env_variable}" id="{$variable.env_variable}" class="form-control">
                            {foreach $variable.options as $option}
                                <option value="{$option}"
                                        {if $environment[$variable.env_variable] == $option}selected{/if}>
                                    {$option}
                                </option>
                            {/foreach}
                        </select>
                    {elseif $variable.rule == 'switch'}
                        <div class="custom-control custom-switch">
                            <input  name="{$variable.env_variable}" value="0" hidden="">
                            <input type="checkbox" class="custom-control-input" id="{$variable.env_variable}"
                                   name="{$variable.env_variable}" value="1"
                                   {if $environment[$variable.env_variable] == '1'}checked{/if}>
                            <label class="custom-control-label" for="{$variable.env_variable}">Enable</label>
                        </div>
                    {elseif $variable.rule == 'number'}
                        <input type="number" name="{$variable.env_variable}" id="{$variable.env_variable}"
                               class="form-control" value="{$environment[$variable.env_variable]}"
                               {if $variable.required}required{/if}>
                    {/if}

                    {if $variable.description}
                        <small class="help-block">{$variable.description}</small>
                    {/if}
                </div>
            </div>

            {if ($key + 1) % 2 == 0 && $key + 1 < $editableVariables|@count}
        </div>
        <hr>
        <div class="row">
            {/if}
            {/foreach}
        </div>

        <button type="button" id="saveButtonSettings"
                data-form-id="saveSettings"
                class="btn btn-primary save-action-pterosync">
            <i class="fa fa-save"></i> {$LANG.clientareaupdatebutton}
        </button>
    </form>
</div>