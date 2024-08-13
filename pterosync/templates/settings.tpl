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

            var button = $(this);
            var formId = button.data('form-id');
            var form = $('#' + formId);

            button.prop("disabled", true);

            // Add spinner to the button and keep the original text
            var originalButtonText = button.html();
            button.html('<span class="spinner-border spinner-border-sm" role="status" id="spinner" aria-hidden="true"></span> ' + originalButtonText);

            // Clear previous error messages and success messages
            $('.error-message').remove();
            $('.success-message').hide();
            form.find('.border-danger').removeClass('border-danger');

            var formData = form.serialize();
            var saveSettingUrl = form.attr('action');

            $.ajax({
                url: saveSettingUrl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    $('.success-message').html(response.message);
                    $('.success-message').fadeIn();

                    // Hide the success message after 5 seconds (5000 milliseconds)
                    setTimeout(function () {
                        $('.success-message').fadeOut();
                    }, 5000);

                    // Remove spinner and re-enable the button
                    button.html(originalButtonText);
                    button.prop("disabled", false);
                },
                error: function (response) {
                    var json = response.responseJSON;
                    if (json.errors) {
                        json.errors.forEach(function (error) {
                            var inputName = error.input;
                            var message = error.message;

                            // Find the input by ID and add the border-danger class
                            var $input = $('#' + inputName);

                            $input.addClass('border-danger');

                            // Optionally, display the error message below the input
                            $input.after('<div class="error-message text-danger">' + message + '</div>');
                        });
                    }

                    // Remove spinner and re-enable the button
                    button.html(originalButtonText);
                    button.prop("disabled", false);
                }
            });
        });
    });

</script>
