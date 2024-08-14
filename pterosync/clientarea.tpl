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
    <div class="tab-pane fade show active text-center" id="overview" role="tabpanel" aria-labelledby="overview-tab">
        {include file="`$moduleDir`/templates/overview.tpl"}
    </div>
    {if $allowStartUpEdit}
        <div class="tab-pane fade" id="startup" role="tabpanel" aria-labelledby="startup-tab">
            {include file="`$moduleDir`/templates/startup.tpl"}
        </div>
    {/if}
    {if $allowSettingsEdit}
        <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
            {include file="`$moduleDir`/templates/settings.tpl"}
        </div>
    {/if}
</div>
<div class="alert alert-success success-message mt-3" style="display: none;"></div>
<div class="alert alert-danger error-message mt-3" style="display: none;"></div>
