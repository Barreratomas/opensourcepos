<?= view('partial/header') ?>

<div id="title_bar" class="btn-toolbar print_hide">
    <button id="new-service" class="btn btn-info btn-sm pull-right">
        <span class="glyphicon glyphicon-tag">&nbsp;</span>Nuevo servicio
    </button>
</div>

<div id="toolbar">
    <div class="pull-left form-inline" role="toolbar">
        <!-- Optional filters here -->
    </div>
</div>

<div id="table_holder">
    <table class="table table-striped table-hover" id="services-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Duración</th>
                <th>Precio</th>
                <th>Activo</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Service modal -->
<div id="serviceModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Detalles del Servicio</h4>
      </div>
      <div class="modal-body">
        <form id="serviceForm" class="form-horizontal">
          <input type="hidden" id="service-id">
          
          <div class="form-group form-group-sm">
            <label class="control-label col-xs-3">Nombre</label>
            <div class="col-xs-8">
                <input id="s-name" class="form-control input-sm">
            </div>
          </div>

          <div class="form-group form-group-sm">
            <label class="control-label col-xs-3">Duración (min)</label>
            <div class="col-xs-8">
                <input id="s-duration" class="form-control input-sm" type="number">
            </div>
          </div>

          <div class="form-group form-group-sm">
            <label class="control-label col-xs-3">Precio</label>
            <div class="col-xs-8">
                <div class="input-group input-group-sm">
                    <span class="input-group-addon"><b>$</b></span>
                    <input id="s-price" class="form-control input-sm" type="number" step="0.01">
                </div>
            </div>
          </div>

          <div class="form-group form-group-sm">
            <label class="control-label col-xs-3">Activo</label>
            <div class="col-xs-8">
                <select id="s-active" class="form-control input-sm">
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" id="delete-service" class="btn btn-danger btn-sm pull-left" style="display:none;">
            <span class="glyphicon glyphicon-trash">&nbsp;</span>Eliminar
        </button>
        <button type="button" id="save-service" class="btn btn-primary btn-sm">
            <span class="glyphicon glyphicon-save">&nbsp;</span>Guardar
        </button>
        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
function loadServices(){
    fetch('appointment_services/list')
    .then(r=>r.json())
    .then(data=>{
        var tbody = document.querySelector('#services-table tbody');
        tbody.innerHTML = '';
        data.forEach(s=>{
            var tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${s.id}</td>
                <td>${s.name}</td>
                <td>${s.duration_minutes} min</td>
                <td>$${s.price}</td>
                <td><span class="label label-${s.active == 1 ? 'success' : 'danger'}">${s.active == 1 ? 'Activo' : 'Inactivo'}</span></td>
                <td style="text-align: right;">
                    <button onclick="editService(${s.id})" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-edit"></span></button>
                    <button onclick="deleteService(${s.id})" class="btn btn-xs btn-danger"><span class="glyphicon glyphicon-trash"></span></button>
                </td>`;
            tbody.appendChild(tr);
        });
    })
}

function editService(id){
    fetch('appointment_services/form/'+id).then(r=>r.json()).then(d=>{
        if (d.service){
            document.getElementById('service-id').value = d.service.id;
            document.getElementById('s-name').value = d.service.name;
            document.getElementById('s-duration').value = d.service.duration_minutes;
            document.getElementById('s-price').value = d.service.price;
            document.getElementById('s-active').value = d.service.active;
            document.getElementById('delete-service').style.display='inline-block';
            $('#serviceModal').modal('show');
        }
    });
}

function deleteService(id){
    if (!confirm('Eliminar servicio?')) return;
    fetch('appointment_services/delete/'+id, { method: 'POST' })
    .then(()=> loadServices());
}

document.getElementById('new-service').addEventListener('click', function(){
    document.getElementById('service-id').value = '';
    document.getElementById('s-name').value = '';
    document.getElementById('s-duration').value = '';
    document.getElementById('s-price').value = '';
    document.getElementById('s-active').value = '1';
    document.getElementById('delete-service').style.display='none';
    $('#serviceModal').modal('show');
});

document.getElementById('save-service').addEventListener('click', function(){
    var id = document.getElementById('service-id').value;
    var body = new URLSearchParams();
    body.append('name', document.getElementById('s-name').value);
    body.append('duration_minutes', document.getElementById('s-duration').value);
    body.append('price', document.getElementById('s-price').value);
    body.append('active', document.getElementById('s-active').value);
    var url = id ? ('appointment_services/update/' + id) : 'appointment_services/create';
    fetch(url, { method: 'POST', body: body }).then(()=>{ $('#serviceModal').modal('hide'); loadServices(); });
});

document.getElementById('delete-service').addEventListener('click', function(){
    var id = document.getElementById('service-id').value;
    if (!id) return;
    if (!confirm('Eliminar servicio?')) return;
    fetch('appointment_services/delete/' + id, { method: 'POST' }).then(()=>{ $('#serviceModal').modal('hide'); loadServices(); });
});

loadServices();
</script>
