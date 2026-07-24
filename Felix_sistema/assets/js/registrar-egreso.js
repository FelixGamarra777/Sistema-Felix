// Registrar Egreso — Compras al Mayor (POS de egresos).
// Reutiliza el mismo motor que Facturación (initPOS, definido en facturacion.js):
// catálogo táctil, numpad, buscador predictivo, carrito reactivo, pagos mixtos y
// la barra de acciones (Vaciar / Guardar / Guardar e Imprimir). La diferencia con
// el POS de ingresos es de negocio, no de interfaz:
//   - escribe egresos con afectación de inventario (AUMENTO/reposición de stock),
//   - la persona es el Proveedor (no el Cliente),
//   - guarda vía guardar_egreso.php con transacción ACID (beginTransaction/commit/rollBack).
function initRegistrarEgreso() {
    initPOS({
        correlativoUrl: 'obtener_correlativo.php?tipo=egreso',
        endpoint: 'guardar_egreso.php',
        docNombre: 'Comprobante',
        modulo: 'compra',                // catálogo restringido a ítems de compra mayorista
        descuentaStock: false,           // una compra al mayor NO descuenta stock (lo repone)
        esMayorista: true,               // muestra el factor de conversión mayor→detal en el catálogo
        cargarPersonas: cargarProveedores,
        personaVacio: 'Sin proveedor',
        personaKey: 'id_proveedor',
        personaModal: {
            modalId: 'modal-proveedor',
            openBtnId: 'btn-open-modal-proveedor',
            closeBtnId: 'close-modal-proveedor',
            nombreId: 'nuevo-proveedor-nombre',
            rifId: 'nuevo-proveedor-rif',
            tipoId: 'nuevo-proveedor-tipo',
            saveBtnId: 'btn-guardar-proveedor',
            endpoint: 'insertar_proveedor.php',
            idKey: 'id_proveedor'
        }
    });
}
