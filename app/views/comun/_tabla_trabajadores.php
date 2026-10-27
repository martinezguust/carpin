<?php
/*
 * =====================================================
 * Carpintería Carpin – app/views/comun/_tabla_trabajadores.php
 * Tabla de trabajadores con su forma de pago y tarifa
 * (HU-30). La usan "Trabajadores y formas de pago" del
 * Contador y "Precios y porcentajes" del Carpintero. El
 * Carpintero no puede editar su propia tarifa (respuesta 16).
 * Variable: $trabajadores, lista con forma de pago y rol.
 * =====================================================
 */

$esCarpinteroActual = Auth::rol() === Auth::CARPINTERO;
?>
<table class="table tabla-carpin tabla-tarjetas">
    <thead>
        <tr>
            <th scope="col">Trabajador</th>
            <th scope="col">Rol</th>
            <th scope="col">Forma de pago</th>
            <th scope="col">Tarifa</th>
            <th scope="col">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($trabajadores as $trabajador): ?>
            <?php
            $configurado = Trabajador::estaConfigurado($trabajador);
            $esPropio = (int) $trabajador['id_usuario'] === Auth::id();
            $urlEditar = View::url('/trabajadores/' . $trabajador['id_trabajador'] . '/editar');

            // Texto de la tarifa según la forma de pago
            $tarifa = match ($trabajador['forma_pago']) {
                'HORA'     => ($trabajador['tarifa_hora'] !== null)
                    ? dinero($trabajador['tarifa_hora']) . ' por hora'
                    : '—',
                'DIA'      => ($trabajador['salario_dia'] !== null)
                    ? dinero($trabajador['salario_dia']) . ' por día'
                    : '—',
                'PROYECTO' => 'Reparto de mano de obra',
                default    => '—',
            };
            ?>
            <tr>
                <td data-etiqueta="Trabajador">
                    <span>
                        <strong><?= e($trabajador['nombre_completo']) ?></strong>
                        <?php if ((int) $trabajador['cuenta_activa'] !== 1): ?>
                            <span class="etiqueta etiqueta--error">Inactivo</span>
                        <?php endif; ?>
                    </span>
                </td>
                <td data-etiqueta="Rol"><?= e(etiquetaRol($trabajador['rol'])) ?></td>
                <td data-etiqueta="Forma de pago">
                    <?php if ($configurado): ?>
                        <?= e(etiquetaFormaPago($trabajador['forma_pago'])) ?>
                    <?php else: ?>
                        <span class="etiqueta etiqueta--advertencia">Sin configurar</span>
                    <?php endif; ?>
                </td>
                <td data-etiqueta="Tarifa"><?= e($tarifa) ?></td>
                <td data-etiqueta="Acciones">
                    <?php if ($esCarpinteroActual && $esPropio): ?>
                        <span class="texto-ayuda">La captura el Contador</span>
                    <?php else: ?>
                        <a href="<?= e($urlEditar) ?>">
                            Editar
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
