<ul class="nav nav-tabs mb-5 d-flex justify-content-center" id="myTab" role="tablist">

    <li role="presentation" class="active">
        <a id="overview-tab" data-toggle="tab"
           href="#overview" role="tab"
           aria-controls="overview" aria-selected="true">
            {$LANG.SERVER_OVERVIEW}
        </a>
    </li>

    {if $allowStartUpEdit}
        <li role="presentation">
            <a id="startup-tab" data-toggle="tab"
               href="#startup" role="tab"
               aria-controls="startup" aria-selected="false">
                {$LANG.SERVER_STARTUP}
            </a>
        </li>
    {/if}
    {if $allowSettingsEdit}
        <li role="presentation">
            <a id="settings-tab" data-toggle="tab"
               href="#settings" role="tab"
               aria-controls="settings" aria-selected="false">
                {$LANG.SERVER_SETTINGS}
            </a>
        </li>
    {/if}

</ul>

<div class="alert alert-success success-message mb-3" style="display: none;"></div>
<div class="alert alert-danger error-message mt-3" style="display: none;"></div>

<div class="tab-content" id="myTabContent">
    <div class="tab-pane active text-center" id="overview" role="tabpanel" aria-labelledby="overview-tab">
        {include file="`$moduleDir`/templates/overview.tpl"}
    </div>

    {if $allowStartUpEdit}
        <div class="tab-pane" id="startup" role="tabpanel" aria-labelledby="startup-tab">
            {include file="`$moduleDir`/templates/startup.tpl"}
        </div>
    {/if}

    {if $allowSettingsEdit}
        <div class="tab-pane" id="settings" role="tabpanel" aria-labelledby="settings-tab">
            {include file="`$moduleDir`/templates/settings.tpl"}
        </div>
    {/if}
</div>

<div class="alert alert-success success-message mt-3" style="display: none;"></div>
<div class="alert alert-danger error-message mt-3" style="display: none;"></div>