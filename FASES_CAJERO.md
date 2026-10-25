# Fases de implementación de módulos de pago

Este documento contiene la hoja de ruta técnica del entorno del cajero. La
definición funcional permanece en [`LOGICA_NEGOCIO.md`](LOGICA_NEGOCIO.md).

## Estado general

| Fase | Alcance | Estado |
|---|---|---|
| 1 | Espacio de trabajo y órdenes asignadas | Completada |
| 2 | Validación de pagos y datos de domicilio | Completada |
| 3 | Facturación y numeración continua | Completada |
| 4 | Módulos de pago, cajas y activación manual | Completada |
| 5 | Movimientos, egresos y traslados | En progreso |
| 6 | Arqueo y cierre de caja | Pendiente |
| 7 | Tiempo real, impresión y auditoría | Pendiente |

## Regla de disponibilidad

El módulo de pago es un registro independiente. Puede existir sin usuario y
contener varias cajas. El usuario se asigna al módulo, no directamente a las
cajas.

El menú de venta muestra módulos de pago con usuario asignado y cajas activas.

Un usuario puede pertenecer al grupo que opera pagos sin tener módulo asignado.
Podrá consultar las órdenes permitidas, pero no podrá registrar pagos,
facturar ni entregar órdenes que requieran un módulo activo.

## Fase 1: espacio de trabajo y órdenes asignadas

El usuario tendrá una vista propia con las órdenes asignadas a su módulo de
pago y que todavía requieren validación o facturación.

### Alcance

- Consultar órdenes asignadas al cajero.
- Ver número de orden y fecha.
- Ver tipo de pedido, mesa o datos resumidos del domicilio.
- Ver compuestos, componentes y total.
- Ver estado operativo y estado de facturación.
- Identificar comprobantes de pago disponibles.
- Separar órdenes facturadas de las pendientes.
- Actualizar la información en tiempo real.

El cajero no podrá editar productos, componentes ni órdenes facturadas.

### Implementado

- Ruta `/caja` protegida por `billing.read`.
- Consulta de órdenes asignadas al usuario cajero actual.
- Detalle de pedido, cliente, domicilio, componentes y total.
- Estados operativo y de facturación visibles.
- Acceso al comprobante de domicilio cuando existe.
- El dashboard solo muestra Caja a usuarios con el permiso correspondiente.

## Fase 2: validación de pagos y domicilio

- Revisar comprobantes cargados desde la orden.
- Reemplazar el comprobante antes de facturar cuando tenga permiso.
- Seleccionar una caja destino; el tipo de pago se obtiene del tipo de dinero
  configurado en esa caja.
- Por defecto se selecciona la caja predeterminada de efectivo y se carga el
  total en el campo de dinero recibido.
- Registrar pago efectivo, digital o mixto sin seleccionar manualmente el
  método.
- En un pago dividido, repartir el total entre las dos cajas predeterminadas.
- Si se selecciona una caja adicional no predeterminada, enviarle el total sin
  solicitar otro método de pago.
- Cuando el pago incluye dinero digital, exigir un comprobante y mantener la
  validación en espera mientras se carga en MinIO.
- Registrar y corregir el valor del domicilio antes de facturar.
- Mostrar el domicilio como concepto separado y no como ingreso del restaurante.
- Calcular cambio cuando el pago en efectivo sea superior al total.

### Implementado

- Formulario de validación dentro de cada orden en `/caja`, optimizado para
  tabletas y móviles.
- Ruta `PATCH /api/caja/orders/{id}/payment` protegida por `billing.create`.
- El pago mixto exige que los valores efectivo y digital sumen exactamente el
  total cobrable.
- Cada distribución exige un monto y una caja perteneciente al módulo
  asignado al cajero; el método se deriva del tipo de dinero de la caja y la
  suma debe coincidir con el pago.
- El valor del domicilio se calcula aparte y queda registrado en la orden,
  sin convertirse en ingreso de venta.
- El cambio se calcula a partir del efectivo recibido y se guarda en la orden.
- Cada validación queda registrada en el historial con usuario, fecha y
  distribución del pago.
- La orden continúa sin facturar; la facturación será una acción separada de
  la fase 3.

## Fase 3: facturación

- Crear la factura mediante una acción independiente.
- Mantener la relación entre factura y número de orden.
- Usar numeración continua de facturas desde `#00000`.
- Conservar el detalle snapshot de los compuestos.
- Registrar el método derivado de la caja y la distribución entre cajas.
- Impedir facturar si faltan datos o validaciones obligatorias.

### Implementado

- Entidad `Invoice` separada de `Order`.
- La factura conserva el número de orden y un snapshot de sus compuestos y
  componentes.
- Numeración continua desde `#00000`, independiente del número diario de la
  orden.
- Botón de facturación separado en la vista del cajero.
- Solo se factura después de validar la caja o distribución del pago.
- La facturación cambia la orden a `facturada`, registra el usuario y agrega
  la acción al historial.
- Las órdenes facturadas ya no pueden modificarse ni volver a facturarse.

## Fase 4: módulos de pago, cajas y activación manual

- Crear el registro de módulo de pago sin usuario obligatorio.
- Asignar o cambiar el usuario responsable del módulo posteriormente.
- Desvincular el usuario del módulo cuando este se encuentre inactivo; las
  cajas permanecen asignadas al módulo.
- Asignar una o varias cajas al módulo según la configuración definida.
- Crear automáticamente dos cajas predeterminadas: Efectivo y Digital.
- Permitir cajas adicionales dentro del mismo módulo.
- Cada caja indica si maneja dinero efectivo o digital; el nombre continúa
  siendo editable y puede repetirse.
- Al comenzar se utilizan dos cajas predeterminadas; se deja una tercera caja
  adicional para pruebas.
- Solo puede haber una predeterminada de efectivo y una predeterminada digital
  por módulo.
- La pantalla financiera muestra un checkbox por caja para elegir las dos
  cajas predeterminadas; al activar una caja del mismo tipo se reemplaza la
  selección anterior.
- Desvincular una caja exige que el módulo esté inactivo y que su saldo sea
  cero; la caja pasa a la lista de cajas libres.
- Impedir que una caja esté asignada simultáneamente a dos módulos.
- Activar manualmente el módulo de pago.
- Desactivar el módulo sin iniciar el cierre.
- Mostrar el módulo como disponible para meseros únicamente cuando esté activo.

### Implementado

- Entidades separadas de módulo de pago y `CashBox`.
- Ruta administrativa `/admin/cajeros` para crear módulos, asignar cajas y
  vincular usuarios posteriormente.
- La misma pantalla permite agregar varias cajas al mismo módulo, por ejemplo
  una física y otra digital.
- Todo módulo tiene una caja predeterminada para efectivo y otra para pagos
  digitales; las cajas adicionales se pueden seleccionar cuando sea necesario.
- La venta solo muestra módulos con usuario asignado, cajas asignadas y módulo activo.
- El usuario puede activar manualmente su módulo desde la ruta de caja.
- El usuario puede desactivarlo sin iniciar el cierre.
- Un módulo desactivado deja de recibir órdenes nuevas, pero permite terminar
  las órdenes que ya estaban asignadas.
- La activación y desactivación están protegidas por permisos de grupo
  `cash.activate` y `cash.deactivate`.
- Las cajas no tienen un tipo independiente; su nombre identifica su uso,
  como `Efectivo`, `Digital` o `Caja central`.
- Toda caja nueva se crea con saldo inicial `0.00`.
- Una caja solo puede eliminarse si está libre y su saldo es exactamente
  `0.00`.

## Fase 5: movimientos, egresos y traslados

- Registrar egresos con valor, motivo y caja de origen.
- Imprimir o simular el comprobante de egreso.
- Trasladar dinero desde cajas libres hacia cualquier caja.
- Trasladar el saldo completo de cajas temporales únicamente hacia cajas libres.
- Impedir transferencias desde cajas asignadas a módulos fuera del cierre.
- Registrar origen, destino, valor, motivo, usuario y fecha.
- Mantener libro mayor de entradas, salidas y traslados.
- Distribuir pagos mixtos entre caja efectiva y digital.

### Base implementada

- Ruta /caja/finanzas para consultar las cajas asignadas al módulo.
- Resumen de saldo actual y órdenes facturadas.
- Total facturado por cada caja según las asignaciones de pago.
- Acceso desde la vista principal del módulo de pago.
- Traslado entre cajas asignadas al módulo activo, con monto y motivo.
- El traslado registra origen, destino, usuario y fecha sin modificar las
  facturaciones.
- Historial de los últimos traslados en la vista financiera.
- Los egresos, las cajas libres, las cajas temporales y el cierre operativo
  quedan como la siguiente parte de esta fase.

## Fase 6: arqueo y cierre

- Permitir arqueos administrativos sin cerrar la caja.
- Solicitar efectivo contado y valor digital declarado.
- Mostrar valores esperados y diferencias.
- Permitir cierre con faltante o sobrante dejando constancia.
- Impedir el cierre mientras existan órdenes sin facturar o sin entregar.
- Mover saldos positivos a cajas temporales al cerrar.
- Permitir que administración vacíe las cajas temporales.
- Reimprimir cierres desde administración.

## Fase 7: tiempo real, impresión y auditoría

- Recibir nuevas órdenes asignadas mediante Mercure.
- Actualizar estados de preparación, facturación y entrega.
- Generar factura térmica de 80 mm mediante el worker.
- Registrar cada acción con usuario, fecha y permiso utilizado.
- Emitir alertas sonoras para eventos relevantes del cajero.
