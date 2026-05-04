<?php
/**
 * Minimal FullCalendar integration for Appointments
 * Expects endpoints:
 *  - GET /appointments/events?start=...&end=...
 *  - POST /appointments/create
 */
?>

<?= view('partial/header') ?>

<div id="title_bar" class="btn-toolbar print_hide">
    <button id="new-appointment" class="btn btn-info btn-sm pull-right">
        <span class="glyphicon glyphicon-calendar">&nbsp;</span>Nuevo turno
    </button>
    <a href="<?= site_url('appointment_services') ?>" class="btn btn-info btn-sm pull-right" style="margin-right: 5px;">
        <span class="glyphicon glyphicon-list-alt">&nbsp;</span>Gestionar Servicios
    </a>
</div>

<div id="toolbar">
    <div class="pull-left form-inline" role="toolbar">
        <div class="form-group form-group-sm">
            <label for="status-filter" class="control-label">Estado:</label>
            <select id="status-filter" class="form-control input-sm selectpicker" data-style="btn-default btn-sm">
                <option value="">Todos</option>
                <option value="pending">Pendiente</option>
                <option value="confirmed">Confirmado</option>
                <option value="completed">Completado</option>
                <option value="cancelled">Cancelado</option>
            </select>
        </div>
    </div>
</div>

<div id="table_holder">
    <div class="panel panel-default">
        <div class="panel-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

<style>
    #calendar { 
        max-width: 100%; 
        margin: 0 auto; 
    }
    .fc-header-toolbar {
        margin-bottom: 20px !important;
    }
    .fc-button-primary {
        background-color: #2c3e50 !important;
        border-color: #2c3e50 !important;
    }
    .fc-event {
        cursor: pointer;
    }
    .ui-autocomplete {
        z-index: 2147483647 !important;
        max-height: 200px;
        overflow-y: auto;
        overflow-x: hidden;
        background-color: white;
        border: 1px solid #ccc;
        box-shadow: 0 5px 10px rgba(0,0,0,0.2);
    }
    .ui-menu-item {
        padding: 5px 10px;
        list-style: none;
        cursor: pointer;
    }
    .ui-menu-item:hover {
        background-color: #f5f5f5;
    }
</style>

<!-- Appointment modal -->
<div id="appointmentModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Detalles del Turno</h4>
      </div>
      <div class="modal-body">
        <form id="appointmentForm" class="form-horizontal">
          <input type="hidden" id="appt-id">
          <input type="hidden" id="customer_id" name="customer_id">
          
          <div class="form-group form-group-sm">
            <label class="control-label col-xs-3">Cliente</label>
            <div class="col-xs-8">
                <input id="customer_name" class="form-control input-sm" placeholder="Buscar cliente...">
            </div>
          </div>

          <div class="form-group form-group-sm">
            <label class="control-label col-xs-3">Empleado</label>
            <div class="col-xs-8">
                <input id="employee_id" name="employee_id" class="form-control input-sm" value="1" placeholder="ID del empleado">
            </div>
          </div>

          <div class="form-group form-group-sm">
            <label class="control-label col-xs-3">Servicios</label>
            <div class="col-xs-8">
                <select id="service_id" name="service_id[]" class="form-control input-sm selectpicker" multiple data-live-search="true" title="-- Seleccione uno o más servicios --"></select>
                <small id="service-info" class="text-muted"></small>
            </div>
          </div>

          <div class="form-group form-group-sm">
            <label class="control-label col-xs-3">Inicio</label>
            <div class="col-xs-8">
                <input id="start_time" name="start_time" type="datetime-local" class="form-control input-sm">
            </div>
          </div>

          <div class="form-group form-group-sm">
            <label class="control-label col-xs-3">Fin</label>
            <div class="col-xs-8">
                <input id="end_time" name="end_time" type="datetime-local" class="form-control input-sm">
            </div>
          </div>

          <div class="form-group form-group-sm">
            <label class="control-label col-xs-3">Estado</label>
            <div class="col-xs-8">
                <select id="status" name="status" class="form-control input-sm">
                  <option value="pending">Pendiente</option>
                  <option value="confirmed">Confirmado</option>
                  <option value="completed">Completado</option>
                  <option value="cancelled">Cancelado</option>
                </select>
            </div>
          </div>

          <div class="form-group form-group-sm">
            <label class="control-label col-xs-3">Notas</label>
            <div class="col-xs-8">
                <textarea id="notes" name="notes" class="form-control input-sm" rows="3"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" id="delete-appt" class="btn btn-danger btn-sm pull-left" style="display:none;">
            <span class="glyphicon glyphicon-trash">&nbsp;</span>Eliminar
        </button>
        <button type="button" id="complete-sale-appt" class="btn btn-success btn-sm" style="display:none;">
            <span class="glyphicon glyphicon-shopping-cart">&nbsp;</span>Completar y Vender
        </button>
        <button type="button" id="save-appt" class="btn btn-primary btn-sm">
            <span class="glyphicon glyphicon-save">&nbsp;</span>Guardar
        </button>
        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
function toSqlDatetime(local) {
    if (!local) return null;
    var d = new Date(local);
    var pad = v => (v<10? '0'+v : v);
    return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':00';
}

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var statusFilter = document.getElementById('status-filter');

    var servicesMap = {};
    fetch('appointment_services/list').then(r=>r.json()).then(list=>{ list.forEach(s=> servicesMap[s.id]=s); populateServices(list); });

    function updateEndTime() {
        var selectedServices = $('#service_id').val() || [];
        var startTime = document.getElementById('start_time').value;
        var info = document.getElementById('service-info');
        
        if (selectedServices.length > 0) {
            var totalDuration = 0;
            var totalPrice = 0;
            
            selectedServices.forEach(id => {
                if (servicesMap[id]) {
                    totalDuration += parseInt(servicesMap[id].duration_minutes);
                    totalPrice += parseFloat(servicesMap[id].price);
                }
            });

            info.textContent = 'Precio Total: $' + totalPrice.toFixed(2) + ' | Duración Total: ' + totalDuration + ' min';
            
            if (startTime) {
                var start = new Date(startTime);
                var end = new Date(start.getTime() + totalDuration * 60000);
                
                // Adjust for timezone offset to local ISO string (YYYY-MM-DDTHH:mm)
                var year = end.getFullYear();
                var month = String(end.getMonth() + 1).padStart(2, '0');
                var day = String(end.getDate()).padStart(2, '0');
                var hours = String(end.getHours()).padStart(2, '0');
                var minutes = String(end.getMinutes()).padStart(2, '0');
                
                var localISOTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                document.getElementById('end_time').value = localISOTime;
            }
        } else {
            info.textContent = '';
        }
    }

    $('#service_id').on('change', updateEndTime);
    document.getElementById('start_time').addEventListener('change', updateEndTime);

    function populateServices(list){
        var sel = document.getElementById('service_id'); sel.innerHTML='';
        list.forEach(s=>{ var o = document.createElement('option'); o.value=s.id; o.textContent = s.name + ' ('+s.duration_minutes+'m)'; sel.appendChild(o); });
        $('.selectpicker').selectpicker('refresh');
    }

    function translateStatus(status) {
        var statuses = {
            'pending': 'Pendiente',
            'confirmed': 'Confirmado',
            'completed': 'Completado',
            'cancelled': 'Cancelado'
        };
        return statuses[status] || status;
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día',
            list: 'Agenda'
        },
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
        navLinks: true,
        selectable: true,
        editable: true,
        selectMirror: true,
        locale: 'es',
        slotLabelFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        select: function(info) {
            openModal({start: info.startStr, end: info.endStr});
        },
        eventClick: function(info) {
            var id = info.event.id;
            fetch('appointments/form/' + id).then(r=>r.json()).then(data=>{
                if (data.appointment) fillModal(data.appointment);
                $('#appointmentModal').modal('show');
            });
        },
        eventDrop: function(info){
            var id = info.event.id;
            var body = new URLSearchParams(); body.append('start_time', toSqlDatetime(info.event.start.toISOString())); body.append('end_time', toSqlDatetime(info.event.end.toISOString()));
            fetch('appointments/update/' + id, { method: 'POST', body: body }).then(()=> calendar.refetchEvents());
        },
        events: function(info, successCallback, failureCallback) {
            var params = new URLSearchParams(); params.append('start', info.startStr); params.append('end', info.endStr);
            fetch('appointments/events?' + params.toString())
                .then(r => r.json())
                .then(data => {
                    if (statusFilter.value) data = data.filter(e=> e.extendedProps && e.extendedProps.status == statusFilter.value);
                    data.forEach(e => {
                        e.title = e.title + ' (' + translateStatus(e.extendedProps.status) + ')';
                    });
                    successCallback(data);
                })
                .catch(err => failureCallback(err));
        }
    });

    calendar.render();

    document.getElementById('new-appointment').addEventListener('click', function(){ openModal(); });
    statusFilter.addEventListener('change', function(){ calendar.refetchEvents(); });

    function openModal(opts){
        document.getElementById('appt-id').value = opts && opts.id ? opts.id : '';
        if (opts && opts.start) document.getElementById('start_time').value = opts.start.substring(0,16);
        if (opts && opts.end) document.getElementById('end_time').value = opts.end.substring(0,16);
        document.getElementById('delete-appt').style.display = opts && opts.id ? 'inline-block' : 'none';
        document.getElementById('complete-sale-appt').style.display = opts && opts.id ? 'inline-block' : 'none';
        $('#service_id').val([]).selectpicker('refresh');
        document.getElementById('service-info').textContent = '';
        $('#appointmentModal').modal('show');
    }

    function fillModal(a){
        document.getElementById('appt-id').value = a.id;
        document.getElementById('customer_id').value = a.customer_id;
        document.getElementById('customer_name').value = a.customer_name || '';
        document.getElementById('employee_id').value = a.employee_id;
        
        // Handle multiple services
        var services = a.services || [a.service_id];
        $('#service_id').val(services).selectpicker('refresh');
        updateEndTime();

        document.getElementById('start_time').value = a.start_time.replace(' ', 'T').substring(0,16);
        document.getElementById('end_time').value = a.end_time.replace(' ', 'T').substring(0,16);
        document.getElementById('status').value = a.status;
        document.getElementById('notes').value = a.notes || '';
        document.getElementById('delete-appt').style.display = 'inline-block';
        document.getElementById('complete-sale-appt').style.display = 'inline-block';
    }

    document.getElementById('complete-sale-appt').addEventListener('click', function(){
        var id = document.getElementById('appt-id').value;
        var customer_id = document.getElementById('customer_id').value;
        var services = $('#service_id').val() || [];
        
        if (!id || !customer_id) {
            alert('Error: Datos del turno incompletos');
            return;
        }

        // Redirect to sales with parameters
        window.location.href = '<?= site_url('sales') ?>?appointment_id=' + id + '&customer_id=' + customer_id + '&service_ids=' + services.join(',');
    });

    document.getElementById('save-appt').addEventListener('click', function(){
        var id = document.getElementById('appt-id').value;
        var body = new URLSearchParams();
        body.append('customer_id', document.getElementById('customer_id').value);
        body.append('employee_id', document.getElementById('employee_id').value);
        
        var services = $('#service_id').val() || [];
        services.forEach(s => body.append('service_id[]', s));

        body.append('start_time', toSqlDatetime(document.getElementById('start_time').value));
        body.append('end_time', toSqlDatetime(document.getElementById('end_time').value));
        body.append('status', document.getElementById('status').value);
        body.append('notes', document.getElementById('notes').value);

        var url = id ? ('appointments/update/' + id) : 'appointments/create';
        fetch(url, { method: 'POST', body: body }).then(r=>r.json()).then(resp=>{ 
            if (resp.error) {
                alert('Error: ' + resp.error);
            } else {
                $('#appointmentModal').modal('hide'); 
                calendar.refetchEvents(); 
            }
        });
    });

    document.getElementById('delete-appt').addEventListener('click', function(){
        var id = document.getElementById('appt-id').value;
        if (!id) return;
        if (!confirm('Eliminar turno?')) return;
        fetch('appointments/delete/' + id, { method: 'POST' }).then(()=>{ $('#appointmentModal').modal('hide'); calendar.refetchEvents(); });
    });

});

// Customer autocomplete (uses existing customers/suggest endpoint)
$(function(){
    var fill_value_customer = function(event, ui) {
        event.preventDefault();
        $("#customer_id").val(ui.item.value);
        $("#customer_name").val(ui.item.label);
    };

    $('#customer_name').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "<?= site_url('customers/suggest') ?>",
                dataType: "json",
                data: {
                    term: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minChars: 1,
        delay: 15,
        cacheLength: 1,
        appendTo: '#appointmentModal',
        select: fill_value_customer,
        focus: fill_value_customer
    });

    // When modal opens clear name if no id
    $('#appointmentModal').on('shown.bs.modal', function(){
        if (!$('#customer_id').val()) $('#customer_name').val('');
    });
});
</script>
