<ul class="nav nav-tabs mb-5 d-flex justify-content-center" id="myTab" role="tablist">

    <li class="nav-item">
        <a class="nav-link active"
           id="overview-tab" data-toggle="tab"
           href="#overview" role="tab"
           aria-controls="overview" aria-selected="true">
            {$LANG.SERVER_OVERVIEW}
        </a>
    </li>


    {if $allowStartUpEdit}
        <li class="nav-item">
            <a class="nav-link"
               id="startup-tab" data-toggle="tab"
               href="#startup" role="tab"
               aria-controls="startup" aria-selected="true">
                {$LANG.SERVER_STARTUP}
            </a>
        </li>
    {/if}
    {if $allowSettingsEdit}
        <li class="nav-item">
            <a class="nav-link"
               id="settings-tab" data-toggle="tab"
               href="#settings" role="tab"
               aria-controls="settings" aria-selected="true">
                {$LANG.SERVER_SETTINGS}
            </a>
        </li>
    {/if}

</ul>
<div class="alert alert-success success-message mb-3" style="display: none;"></div>
<div class="alert alert-danger error-message mt-3" style="display: none;"></div>

<div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
        {include file='/home/control.qgs-hosting.com/public_html/modules/servers/pterosync/templates/overview.tpl'}
    </div>
    {if $allowStartUpEdit}
        <div class="tab-pane fade" id="startup" role="tabpanel" aria-labelledby="startup-tab">

        </div>
    {/if}
    {if $allowSettingsEdit}
        <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
            <h3 class="card-title mb-4">
                {$LANG.SERVER_SETTINGS}
            </h3>
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


            <script>
                $(document).ready(function () {
                    $('.save-action-pterosync').on('click', function (e) {
                        e.preventDefault();

                        const button = $(this),
                            formId = button.data('form-id'),
                            form = $('#' + formId),
                            originalButtonText = button.html();

                        button.prop("disabled", true)
                            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + originalButtonText);

                        // Clear previous errors and success messages
                        $('.error-messages, .success-message').removeClass('border-danger').hide();
                        form.find('.border-danger').removeClass('border-danger');

                        $.ajax({
                            url: form.attr('action'),
                            type: 'POST',
                            data: form.serialize(),
                            success: function (response) {
                                $('.success-message').html(response.message).fadeIn().delay(5000).fadeOut();
                            },
                            error: function (response) {
                                const json = response.responseJSON;
                                if (json.errors) {
                                    json.errors.forEach(function (error) {
                                        $('#' + error.input).addClass('border-danger')
                                            .after('<div class="error-messages text-danger">' + error.message + '</div>');
                                    });
                                } else {
                                    $('.error-message').html(json.message).fadeIn().delay(5000).fadeOut();
                                }
                            },
                            complete: function () {
                                button.html(originalButtonText).prop("disabled", false);
                            }
                        });
                    });
                });

            </script>

        </div>
    {/if}
</div>
<div class="alert alert-success success-message mt-3" style="display: none;"></div>
<div class="alert alert-danger error-message mt-3" style="display: none;"></div>