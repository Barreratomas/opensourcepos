<?php
/**
 * @var array $mailchimp
 * @var string $controller_name
 */
?>

<?= form_open('config/saveMailchimp/', ['id' => 'mailchimp_config_form', 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal']) ?>
    <div id="config_wrapper">
        <fieldset id="config_info">

            <div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
            <div id="integrations_header"><?= lang('Config.mailchimp_configuration') ?></div>
            <ul id="mailchimp_error_message_box" class="error_message_box"></ul>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Config.mailchimp_api_key'), 'mailchimp_api_key', ['class' => 'control-label col-xs-2']) ?>
                <div class="col-xs-4">
                    <div class="input-group">
                        <span class="input-group-addon input-sm">
                            <span class="glyphicon glyphicon-cloud"></span>
                        </span>
                        <?= form_input([
                            'name'  => 'mailchimp_api_key',
                            'id'    => 'mailchimp_api_key',
                            'class' => 'form-control input-sm',
                            'value' => $mailchimp['api_key']
                        ]) ?>
                    </div>
                </div>
                <div class="col-xs-1">
                    <label class="control-label">
                        <a href="https://eepurl.com/b9a05b" target="_blank">
                            <span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="right" title="<?= lang('Config.mailchimp_tooltip') ?>"></span>
                        </a>
                    </label>
                </div>
            </div>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Config.mailchimp_lists'), 'mailchimp_list_id', ['class' => 'control-label col-xs-2']) ?>
                <div class="col-xs-4">
                    <div class="input-group">
                        <span class="input-group-addon input-sm">
                            <span class="glyphicon glyphicon-user"></span>
                        </span>
                        <?= form_dropdown(
                            'mailchimp_list_id',
                            $mailchimp['lists'],
                            $mailchimp['list_id'],
                            'id="mailchimp_list_id" class="form-control input-sm"'
                        ) ?>
                    </div>
                </div>
            </div>

            <?= form_submit([
                'name'  => 'submit_mailchimp',
                'id'    => 'submit_mailchimp',
                'value' => lang('Common.submit'),
                'class' => 'btn btn-primary btn-sm pull-right'
            ]) ?>

        </fieldset>
    </div>
<?= form_close() ?>

<br />

<?= form_open('config/saveGoogleCalendar/', ['id' => 'google_calendar_config_form', 'enctype' => 'multipart/form-data', 'class' => 'form-horizontal']) ?>
    <div id="config_wrapper">
        <fieldset id="config_info">
            <div id="integrations_header">Google Calendar</div>
            
            <div class="form-group form-group-sm">
                <?= form_label('Client ID', 'gcalendar_client_id', ['class' => 'control-label col-xs-2']) ?>
                <div class="col-xs-4">
                    <?= form_input([
                        'name'  => 'gcalendar_client_id',
                        'id'    => 'gcalendar_client_id',
                        'class' => 'form-control input-sm',
                        'value' => $gcalendar['client_id'] ?? ''
                    ]) ?>
                </div>
            </div>

            <div class="form-group form-group-sm">
                <?= form_label('Client Secret', 'gcalendar_client_secret', ['class' => 'control-label col-xs-2']) ?>
                <div class="col-xs-4">
                    <?= form_input([
                        'name'  => 'gcalendar_client_secret',
                        'id'    => 'gcalendar_client_secret',
                        'type'  => 'password',
                        'class' => 'form-control input-sm',
                        'value' => $gcalendar['client_secret'] ?? ''
                    ]) ?>
                </div>
            </div>

            <div class="form-group form-group-sm">
                <?= form_label('Redirect URI', '', ['class' => 'control-label col-xs-2']) ?>
                <div class="col-xs-6">
                    <input type="text" class="form-control input-sm" value="<?= site_url('appointments/google_callback') ?>" readonly>
                    <span class="help-block">Copia este URI en tu consola de Google Cloud (OAuth 2.0 Client IDs).</span>
                </div>
            </div>

            <div class="form-group form-group-sm">
                <label class="control-label col-xs-2">Estado</label>
                <div class="col-xs-4">
                    <?php if (!empty($gcalendar['token'])): ?>
                        <span class="label label-success">Conectado</span>
                        <a href="<?= site_url('appointments/google_authorize') ?>" class="btn btn-default btn-xs">Reconectar</a>
                    <?php else: ?>
                        <span class="label label-danger">No conectado</span>
                        <a href="<?= site_url('appointments/google_authorize') ?>" class="btn btn-primary btn-xs">Conectar con Google</a>
                    <?php endif; ?>
                </div>
            </div>

            <?= form_submit([
                'name'  => 'submit_gcalendar',
                'id'    => 'submit_gcalendar',
                'value' => lang('Common.submit'),
                'class' => 'btn btn-primary btn-sm pull-right'
            ]) ?>
        </fieldset>
    </div>
<?= form_close() ?>

<script type="text/javascript">
    // Validation and submit handling
    $(document).ready(function() {
        $('#mailchimp_api_key').change(function() {
            $.post("<?= "$controller_name/checkMailchimpApiKey" ?>", {
                    'mailchimp_api_key': $('#mailchimp_api_key').val()
                },
                function(response) {
                    $.notify({
                        message: response.message
                    }, {
                        type: response.success ? 'success' : 'danger'
                    });
                    $('#mailchimp_list_id').empty();
                    $.each(response.mailchimp_lists, function(val, text) {
                        $('#mailchimp_list_id').append(new Option(text, val));
                    });
                    $('#mailchimp_list_id').prop('selectedIndex', 0);
                },
                'json'
            );
        });

        $('#mailchimp_config_form').validate($.extend(form_support.handler, {
            submitHandler: function(form) {
                $(form).ajaxSubmit({
                    success: function(response) {
                        $.notify({
                            message: response.message
                        }, {
                            type: response.success ? 'success' : 'danger'
                        })
                    },
                    dataType: 'json'
                });
            },

            errorLabelContainer: '#mailchimp_error_message_box'
        }));

        $('#google_calendar_config_form').validate($.extend(form_support.handler, {
            submitHandler: function(form) {
                $(form).ajaxSubmit({
                    success: function(response) {
                        $.notify({
                            message: response.message
                        }, {
                            type: response.success ? 'success' : 'danger'
                        })
                    },
                    dataType: 'json'
                });
            }
        }));
    });
</script>
