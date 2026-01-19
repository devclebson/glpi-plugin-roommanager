<?php
// plugins/roommanager/front/booking.php
include ("../../../inc/includes.php");

Session::checkLoginUser();
Html::header("Reserva de Salas", $_SERVER['PHP_SELF'], "tools", "plugin_roommanager_booking");
?>

<style>
    /* --- CORREÇÃO DA LOGO --- */
    :root {
        /* Força o GLPI a buscar a imagem na raiz, e não dentro do plugin */
        --bar-logo-glpi: url('/pics/scc/logo_scc.png') !important;
        --bar-logo-glpi-collapsed: url('/pics/scc/logo_scc.png') !important;
    }
    /* --- 1. VISUAL DOS CAMPOS --- */
    .roommanager-container select.form-select,
    .roommanager-container input.form-control {
        color: #212529 !important; background-color: #ffffff !important; border: 1px solid #ced4da !important;
    }
    .roommanager-container label { white-space: nowrap !important; display: block !important; width: 100% !important; margin-bottom: 5px; }

    /* --- 2. ESTRUTURA DA GRADE --- */
    .room-grid { 
        width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 20px; background: #fff; border-radius: 8px; 
    }

    .room-grid th, .room-grid td { 
        border-bottom: 1px solid #eee; padding: 10px; text-align: center; vertical-align: top; 
    }

    .room-grid th { 
        background-color: #f8f9fa; border-bottom: 2px solid #ddd; color: #495057; vertical-align: middle; 
        min-width: 130px; 
    }

    /* --- 3. COLUNA FIXA DA SALA (CORRIGIDA) --- */
    .room-col { 
        position: sticky !important; 
        left: 0; 
        z-index: 10; 
        background-color: #fff !important; 
        width: 260px; 
        min-width: 260px; /* Garante largura fixa */
        text-align: left !important; 
        vertical-align: middle !important;
        border-right: 2px solid #e9ecef; 
        box-shadow: 4px 0 5px -2px rgba(0,0,0,0.05);
    }
    
    /* Garante que o texto dentro da sala não quebre errado */
    .room-info-container {
        display: flex;
        align-items: center;
        width: 100%;
    }
    .room-icon {
        flex-shrink: 0; /* Ícone não encolhe */
    }
    .room-text {
        flex-grow: 1; /* Texto ocupa o resto */
        min-width: 0; /* Permite que o texto quebre linha se necessário */
        padding-right: 10px;
    }

    /* Canto Superior Esquerdo */
    thead th.room-col { z-index: 20; background-color: #f8f9fa !important; border-bottom: 2px solid #ddd; }

    /* --- 4. COMPONENTES --- */
    .btn-reservar { background-color: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; width: 100%; font-weight: 600; font-size: 0.9em; transition: 0.2s; margin-top: 5px; }
    .btn-reservar:hover { background-color: #218838; transform: translateY(-1px); }
    
    .ocupado { 
        background-color: #ffebee; color: #c62828; padding: 8px; 
        border-radius: 4px; font-size: 0.85em; border: 1px solid #ffcdd2; text-align: left;
        min-height: 55px; display: flex; flex-direction: column; justify-content: center;
    }
    .minha-reserva { background-color: #e3f2fd; color: #1565c0; border-color: #bbdefb; position: relative; padding-right: 25px; }
    
    .event-title { font-weight: bold; display: block; margin-bottom: 4px; font-size: 1.05em; white-space: normal; line-height: 1.2; }
    .event-user { font-size: 0.85em; opacity: 0.85; display: block; }
    
    .btn-cancelar { position: absolute; right: 2px; top: 2px; color: #d32f2f; cursor: pointer; padding: 4px; border-radius: 50%; border: none; background: transparent; }
    .btn-cancelar:hover { background-color: rgba(211, 47, 47, 0.1); }

    .expirado { background-color: #f8f9fa; color: #adb5bd; border: 1px dashed #dee2e6; padding: 8px; border-radius: 5px; font-size: 0.85em; cursor: not-allowed; min-height: 55px; display: flex; align-items: center; justify-content: center;}
</style>

<?php
global $DB;
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_loc  = isset($_GET['location']) ? (int)$_GET['location'] : 0; 
$my_uid        = Session::getLoginUserID();

$now_obj = new DateTime(); 
$selected_date_obj = new DateTime($selected_date);
$is_past_date = $selected_date_obj->format('Y-m-d') < $now_obj->format('Y-m-d');
$is_today     = $selected_date_obj->format('Y-m-d') === $now_obj->format('Y-m-d');

// --- LOCAIS ---
$locations = [];
$iterator = $DB->request(['FROM' => 'glpi_locations', 'ORDER' => 'completename']);
foreach ($iterator as $loc) {
    if (strpos($loc['completename'], 'Grupo SCC') === 0) {
        $locations[$loc['id']] = $loc['completename'];
    }
}
if ($selected_loc === 0 && count($locations) > 0) {
    foreach($locations as $id => $name) {
        if (stripos($name, 'Florianópolis') !== false) { $selected_loc = $id; break; }
    }
    if ($selected_loc === 0) $selected_loc = array_key_first($locations);
}

// --- SALAS ---
$where_rooms = ['is_active' => 1, 'is_deleted' => 0];
if ($selected_loc > 0) $where_rooms['locations_id'] = $selected_loc;
$rooms = [];
foreach ($DB->request(['FROM' => 'glpi_plugin_roommanager_rooms', 'WHERE' => $where_rooms]) as $item) $rooms[] = $item;

// --- SLOTS & RESERVAS ---
$slots = [];
foreach ($DB->request(['FROM' => 'glpi_plugin_roommanager_slots', 'ORDER' => 'start_time ASC']) as $item) $slots[] = $item;

$bookings_map = [];
$iter = $DB->request([
    'SELECT' => ['glpi_plugin_roommanager_bookings.*', 'glpi_users.name AS username'],
    'FROM'   => 'glpi_plugin_roommanager_bookings',
    'LEFT JOIN' => ['glpi_users' => ['FKEY' => ['glpi_plugin_roommanager_bookings' => 'users_id', 'glpi_users' => 'id']]],
    'WHERE'  => ['date' => $selected_date]
]);
foreach ($iter as $b) {
    $bookings_map[$b['plugin_roommanager_rooms_id']][$b['plugin_roommanager_slots_id']] = $b;
}
?>

<div class="roommanager-container card m-4 border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="m-0"><i class="ti ti-calendar"></i> Grade de Reservas</h2>
        </div>
        
        <form method="GET" class="row g-3 p-3 bg-light rounded mb-4 border align-items-end">
            <div class="col-md-5">
                <label class="fw-bold form-label text-muted small">SEDE / LOCALIZAÇÃO</label>
                <select name="location" class="form-select shadow-sm" onchange="this.form.submit()">
                    <?php foreach ($locations as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($selected_loc == $id) ? 'selected' : ''; ?>>
                            <?php echo $name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="fw-bold form-label text-muted small">DATA DESEJADA</label>
                <input type="date" name="date" class="form-control shadow-sm" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="ti ti-refresh"></i> Filtrar</button>
            </div>
        </form>

        <?php if (empty($rooms)): ?>
            <div class="alert alert-warning"><i class="ti ti-alert-triangle"></i> Nenhuma sala encontrada nesta localização.</div>
        <?php else: ?>
            <div class="table-responsive shadow-sm rounded">
                <table class="room-grid">
                    <thead>
                        <tr>
                            <th class="room-col bg-light">Salas</th>
                            <?php foreach ($slots as $slot): ?>
                                <th>
                                    <?php echo $slot['name']; ?><br>
                                    <small class="text-muted fw-normal">
                                        <?php echo substr($slot['start_time'], 0, 5) . ' - ' . substr($slot['end_time'], 0, 5); ?>
                                    </small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td class="room-col">
                                    <div class="room-info-container">
                                        <div class="room-icon bg-light rounded p-2 me-2 text-primary"><i class="ti ti-building"></i></div>
                                        <div class="room-text">
                                            <div class="fw-bold"><?php echo $room['name']; ?></div>
                                            <div class="small text-muted" style="white-space: nowrap;">Capacidade: <?php echo $room['capacity']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <?php foreach ($slots as $slot): ?>
                                    <?php 
                                        $is_booked = isset($bookings_map[$room['id']][$slot['id']]);
                                        $booking = $is_booked ? $bookings_map[$room['id']][$slot['id']] : null;
                                        $is_mine = $is_booked && ($booking['users_id'] == $my_uid);
                                        
                                        $is_expired = false;
                                        if ($is_past_date) $is_expired = true;
                                        elseif ($is_today) {
                                            if ($now_obj > new DateTime($selected_date . ' ' . $slot['start_time'])) $is_expired = true;
                                        }
                                        
                                        $display_title = (!empty($booking['name'])) ? $booking['name'] : "Reservado";
                                    ?>
                                    <td>
                                        <?php if ($is_booked): ?>
                                            <div class="ocupado <?php echo $is_mine ? 'minha-reserva' : ''; ?>">
                                                <span class="event-title" title="<?php echo $display_title; ?>">
                                                    <?php echo ($is_mine ? '<i class="ti ti-check-circle"></i> ' : '<i class="ti ti-lock"></i> ') . mb_strimwidth($display_title, 0, 50, "..."); ?>
                                                </span>
                                                <span class="event-user"><?php echo mb_strimwidth($booking['username'], 0, 20, "."); ?></span>
                                                
                                                <?php if ($is_mine && !$is_expired): ?>
                                                    <button class="btn-cancelar btn-delete" title="Cancelar" data-booking-id="<?php echo $booking['id']; ?>">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($is_expired): ?>
                                            <div class="expirado" title="Horário expirado"><i class="ti ti-clock-off"></i> --</div>
                                        <?php else: ?>
                                            <button class="btn-reservar btn-open-modal" 
                                                    data-room-id="<?php echo $room['id']; ?>" 
                                                    data-slot-id="<?php echo $slot['id']; ?>"
                                                    data-room-name="<?php echo $room['name']; ?>"
                                                    data-slot-name="<?php echo $slot['name']; ?>">
                                                + Reservar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalReserva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-calendar-plus"></i> Nova Reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2">
                    <strong>Local:</strong> <span id="modalRoomName"></span><br>
                    <strong>Horário:</strong> <span id="modalSlotName"></span>
                </div>
                <form id="formReserva">
                    <input type="hidden" name="room_id" id="inputRoomId">
                    <input type="hidden" name="slot_id" id="inputSlotId">
                    <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                    
                    <div class="mb-3">
                        <label for="eventTitle" class="form-label fw-bold">Título da Reunião / Evento <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="eventTitle" name="event_name" placeholder="Ex: Reunião de Equipe, Entrevista..." required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmReserva">Confirmar Reserva</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                      document.querySelector('meta[property="glpi:csrf_token"]')?.getAttribute('content');
    const url = "<?php echo $CFG_GLPI['root_doc']; ?>/plugins/roommanager/ajax/booking.php";
    
    let modalReserva;
    const modalEl = document.getElementById('modalReserva');
    if (typeof bootstrap !== 'undefined') {
        modalReserva = new bootstrap.Modal(modalEl);
    }

    document.querySelectorAll('.btn-open-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modalRoomName').innerText = this.dataset.roomName;
            document.getElementById('modalSlotName').innerText = this.dataset.slotName;
            document.getElementById('inputRoomId').value = this.dataset.roomId;
            document.getElementById('inputSlotId').value = this.dataset.slotId;
            const titleInput = document.getElementById('eventTitle');
            titleInput.value = "";
            modalReserva.show();
            setTimeout(() => titleInput.focus(), 500);
        });
    });

    document.getElementById('btnConfirmReserva').addEventListener('click', function() {
        const titleInput = document.getElementById('eventTitle');
        if (titleInput.value.trim() === "") {
            alert("Por favor, informe um título.");
            titleInput.focus();
            return;
        }

        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-loader animate-spin"></i> Salvando...';
        btn.disabled = true;

        const formData = new FormData(document.getElementById('formReserva'));
        formData.append('action', 'add');

        sendRequest(formData, btn, originalText);
    });

    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            if(!confirm("Cancelar esta reserva?")) return;
            const btnEl = this;
            btnEl.innerHTML = '<i class="ti ti-loader animate-spin"></i>'; 
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('booking_id', this.dataset.bookingId);
            
            const headers = {};
            if (csrfToken) { headers['X-Glpi-Csrf-Token'] = csrfToken; headers['X-Requested-With'] = 'XMLHttpRequest'; }

            fetch(url, { method: 'POST', body: fd, headers: headers })
            .then(res => res.json())
            .then(data => {
                if(data.success) window.location.reload();
                else alert(data.message);
            });
        });
    });

    function sendRequest(formData, btn, originalText) {
        const headers = {};
        if (csrfToken) {
            headers['X-Glpi-Csrf-Token'] = csrfToken;
            headers['X-Requested-With'] = 'XMLHttpRequest';
        }

        fetch(url, { method: 'POST', body: formData, headers: headers })
        .then(res => res.ok ? res.json() : res.text().then(t => { throw new Error(t) }))
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            alert("Erro ao processar.");
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
});
</script>
<?php Html::footer(); ?>