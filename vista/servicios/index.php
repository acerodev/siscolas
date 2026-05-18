<?php
include "../../modelo/conexion.php";
include "../../control/auth.php";
include "../../control/permisos.php";

permitirSolo(["Super Admin", "Admin"]);

include "../../controlador/servicios/eliminar_servicio.php";
include "../../controlador/servicios/registrar_servicio.php";
include "../../controlador/servicios/modificar_servicio.php";
//VERIFICAR YA QUE DEMORA mucho en cargar
include "../../controlador/atencion/notificar_socket.php";
include "../header.php";
?>

<div class="container-fluid py-4">

    <!-- Encabezado -->
    <div class="page-header-card mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2>
                    <i class="fa-solid fa-concierge-bell me-2"></i>
                    Gestión de Servicios
                </h2>
                <p>
                    Administra los servicios disponibles para la generación y atención de tickets.
                </p>
            </div>

            <div class="col-lg-4 text-end d-none d-lg-block">
                <i class="fa-solid fa-layer-group"
                    style="font-size: 4.5rem; opacity: 0.15;"></i>
            </div>
        </div>
    </div>

    <!-- Tarjeta principal -->
    <div class="card content-card">
        <div class="card-body p-4">

            <!-- Barra superior -->
            <div class="row g-3 align-items-center mb-4">

                <!-- Botón Nuevo Servicio -->
                <div class="col-md-4">
                    <button type="button"
                        class="btn btn-primary rounded-pill px-4"
                        data-bs-toggle="modal"
                        data-bs-target="#modalRegistro">
                        <i class="fa-solid fa-plus me-2"></i>
                        Nuevo Servicio
                    </button>
                </div>

                <!-- Título -->
                <div class="col-md-4 text-center">
                    <h5 class="mb-0 fw-bold">Lista de Servicios</h5>
                </div>

                <!-- Buscador -->
                <div class="col-md-4">
                    <form method="GET" class="search-box">
                        <div class="input-group">
                            <input type="text"
                                name="buscar"
                                class="form-control"
                                placeholder="Buscar servicio..."
                                value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">

                            <button class="btn btn-primary" type="submit">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- Tabla -->
            <div class="table-responsive">

                <table class="table table-hover table-modern align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Código</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        $registrosPorPagina = 10;

                        // Página actual
                        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
                        if ($pagina < 1) $pagina = 1;

                        // Calcular inicio
                        $inicio = ($pagina - 1) * $registrosPorPagina;

                        //BUSCAR REGISTRO
                        $buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : "";
                        $where = "";

                        if (!empty($buscar)) {
                            $buscar = $conexion->real_escape_string($buscar);
                            $where = "WHERE nombre_serv LIKE '%$buscar%' 
                                            OR codigo_serv LIKE '%$buscar%'";
                        }

                        // Contar total registros
                        $totalRegistrosQuery = $conexion->query("
                                    SELECT COUNT(*) as total 
                                    FROM servicios s
                                    $where
                                ");

                        $totalRegistros = $totalRegistrosQuery->fetch_object()->total;

                        // Total páginas
                        $totalPaginas = ceil($totalRegistros / $registrosPorPagina);


                        $sql = $conexion->query("SELECT * FROM servicios
                                                $where                                                
                                                ORDER BY nombre_serv ASC
                                                LIMIT $inicio, $registrosPorPagina");

                        // Antes del while, agregar numeración correlativa
                        $contador = $inicio + 1;

                        while ($datos = $sql->fetch_object()) { ?>

                            <tr>
                                <td><?= $datos->id_servicios ?></td>
                                <td><?= $datos->nombre_serv ?></td>
                                <td><span class="badge badge-custom">
                                        <?= htmlspecialchars($datos->codigo_serv) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $datos->estado_serv == 1 ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $datos->estado_serv == 1 ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 justify-content-center action-buttons">

                                        <!-- Editar -->
                                        <button class="btn btn-warning btn-sm btnEditar"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditar"
                                            data-id="<?= $datos->id_servicios ?>"
                                            data-nombre="<?= htmlspecialchars($datos->nombre_serv) ?>"
                                            data-codigo="<?= htmlspecialchars($datos->codigo_serv) ?>"
                                            title="Editar Servicio">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <!-- Activar / Desactivar -->
                                        <?php if ($datos->estado_serv == 1): ?>
                                            <a href="#"
                                                class="btn btn-danger btn-sm btnDesactivar"
                                                data-id="<?= $datos->id_servicios ?>"
                                                title="Desactivar Servicio">
                                                <i class="fa-solid fa-circle-xmark"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="#"
                                                class="btn btn-success btn-sm btnActivar"
                                                data-id="<?= $datos->id_servicios ?>"
                                                title="Activar Servicio">
                                                <i class="fa-solid fa-circle-check"></i>
                                            </a>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                        <?php } ?>

                    </tbody>
                </table>

                <!-- MODAL EDITAR -->
                <div class="modal fade" id="modalEditar" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">

                            <div class="modal-header bg-warning">
                                <h5 class="modal-title">
                                    <i class="fa-solid fa-pen-to-square me-2"></i>
                                    Editar Servicio
                                </h5>
                                <button type="button" class="btn-close"
                                    data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">

                                <form method="POST" class="formEditarServicio">

                                    <input type="hidden" name="id" id="edit_id">

                                    <div class="mb-3">
                                        <label class="form-label">Nombre</label>
                                        <input type="text"
                                            class="form-control"
                                            name="nombre"
                                            id="edit_nombre"
                                            required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Código</label>
                                        <input type="text"
                                            class="form-control"
                                            name="codigo"
                                            id="edit_codigo"
                                            required>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fa-solid fa-floppy-disk me-2"></i>
                                            Guardar Cambios
                                        </button>
                                    </div>

                                </form>

                            </div>

                        </div>
                    </div>
                </div>

                <nav class="mt-4">
                    <ul class="pagination justify-content-center">

                        <!-- Botón anterior -->
                        <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&buscar=<?= urlencode($buscar) ?>">Anterior</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $i ?>&buscar=<?= urlencode($buscar) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Botón siguiente -->
                        <li class="page-item <?= ($pagina >= $totalPaginas) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&buscar=<?= urlencode($buscar) ?>">Siguiente</a>
                        </li>

                    </ul>
                </nav>
            </div>

        </div>
    </div>

</div>
</div>

</div>

<!-- BOTÓN FLOTANTE -->
<button type="button"
    class="btn btn-primary floating-btn"
    data-bs-toggle="modal"
    data-bs-target="#modalRegistro"
    title="Registrar Servicio">
    <i class="fa-solid fa-plus"></i>
</button>

<!-- MODAL -->
<div class="modal fade" id="modalRegistro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Registrar Servicio</h5>
                <button type="button" class="btn-close btn-close-white"
                    data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <form method="POST" class="formRegistrarServicio">
                    <input type="hidden" name="btnregistrarServicio" value="ok">

                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Código</label>
                        <input type="text" class="form-control" name="codigo" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado" required>
                            <option value="">Seleccione estado</option>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="d-grid">
                        <button type="button"
                            class="btn btn-primary btnConfirmarRegistro">
                            Registrar
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>public/js/servicios.js"></script>

<?php
include "../footer.php";
?>