# Fases de implementación del mesero

Este documento contiene únicamente la hoja de ruta técnica para implementar el
entorno del mesero. La definición de negocio permanece en
[`LOGICA_NEGOCIO.md`](LOGICA_NEGOCIO.md).

## Estado general

| Fase | Alcance | Estado |
|---|---|---|
| 1 | Crear y enviar órdenes | Completada |
| 2 | Consultar órdenes pendientes | Completada |
| 3 | Editar órdenes y bloquear edición simultánea | Completada |
| 4 | Editar componentes de los ítems | Completada |
| 5 | Historial y trazabilidad | Completada |
| 6 | Datos y comprobantes de domicilio | Completada |
| 7 | Impresión de comandas | Simulada en PDF; impresora física pendiente |
| 8 | Actualizaciones en tiempo real | Base implementada |
| 9 | Permisos por grupos | Base de permisos y grupos creada; integración pendiente |

## Fase 1: crear y enviar órdenes

### Implementado

- Ruta `/venta` para el menú de venta.
- Catálogo de productos y compuestos activos.
- Carrito con cantidades y total preliminar.
- Creación de compuestos personalizados desde la misma vista.
- Los productos individuales se agregan como compuestos de un solo componente.
- Edición de los componentes de un compuesto antes de enviarlo.
- Precio del compuesto calculado como la suma de los productos seleccionados.
- Selección del tipo de pedido:
  - Mesa.
  - Llevar.
  - Domicilio.
- Selección del cajero responsable.
- Actualmente se muestran cajeros del grupo `cajero` como solución temporal.
- Cuando exista el módulo de cajas, solo se mostrarán cajeros con una caja activa.
- Número de mesa para pedidos en mesa.
- Nombre, teléfono y dirección para domicilios.
- Endpoint `POST /api/orders`.
- Numeración diaria de órdenes.
- Estado inicial `en_preparacion`.
- Estado de facturación inicial `sin_facturar`.
- Creación de snapshots desacoplados del catálogo.
- Validación de productos y compuestos activos.

### Verificación

- Orden de prueba `#1` creada desde la sesión de `mesero`.
- Orden asignada al usuario `cajero`.
- Compuesto de prueba guardado con sus cuatro componentes.
- Esquema Doctrine sincronizado.

## Fase 2: consultar órdenes pendientes

### Implementado

- Ruta `/ordenes` para consultar órdenes activas.
- Endpoint `GET /api/orders`.
- Exclusión de órdenes en estado `entregada`.
- Visualización del número y fecha de la orden.
- Visualización del tipo de pedido y mesa o cliente.
- Visualización del cajero asignado.
- Visualización del usuario creador.
- Visualización de estados operativo y de facturación.
- Visualización del total y componentes snapshot.
- Acceso desde el dashboard operativo.

### Verificación

- La orden de prueba `#1` aparece en la vista.
- La API devuelve sus cuatro componentes.
- La vista y la API requieren un usuario autenticado.

Crear una vista para que los usuarios autorizados consulten las órdenes activas
que todavía no hayan sido entregadas. La vista deberá mostrar como mínimo:

- Número y hora de la orden.
- Tipo de pedido.
- Mesa o datos resumidos del domicilio.
- Cajero asignado.
- Estado operativo.
- Estado de facturación.
- Total calculado.
- Usuario que creó la orden.

La consulta no deberá mostrar normalmente órdenes ya entregadas al grupo
predeterminado de meseros.

## Fase 3: editar órdenes y bloquear edición

### Implementado

- Ruta `/ordenes/{id}/editar`.
- Bloqueo exclusivo por usuario durante cinco minutos.
- Liberación del bloqueo al guardar o cancelar.
- Liberación automática cuando el bloqueo vence.
- Rechazo de edición para órdenes facturadas.
- Edición de tipo de pedido, mesa y datos del domicilio.
- Componentes visibles en modo lectura hasta la fase 4.
- Endpoints `PATCH /api/orders/{id}` y `DELETE /api/orders/{id}/lock`.

### Pendiente de esta fase

- Las acciones de edición deberán vincularse a permisos de grupos en la fase 9.

Implementar la apertura de una orden para edición con bloqueo temporal:

- Todos los meseros podrán consultar las órdenes visibles.
- Solo un usuario podrá editar una orden a la vez.
- El bloqueo registrará usuario y fecha de inicio.
- Se liberará al guardar, cancelar o cerrar la edición.
- Se podrá liberar automáticamente un bloqueo vencido por inactividad.
- Las órdenes facturadas no podrán editarse.

## Fase 4: editar componentes

### Implementado

- Agregar componentes activos a un ítem.
- Reemplazar componentes existentes.
- Copiar la medida entera definida en el producto.
- No editar directamente medida, costo ni precio del componente.
- Quitar componentes sin dejar un ítem vacío.
- Mantener el precio y los datos snapshot del componente.
- Mantener el bloqueo de la orden mientras se realizan cambios.
- Interfaz de edición dentro de la orden.
- Endpoints para agregar, actualizar y quitar componentes.
- En el menú de venta se pueden armar y editar compuestos personalizados antes de crear la orden.

### Pendiente de esta fase

- Los permisos específicos para cada acción se conectarán en la fase 9.

Permitir modificar únicamente la copia de la orden, sin cambiar el catálogo:

- Agregar un componente activo.
- Quitar un componente.
- Reemplazar un componente.
- Cambiar cantidades.
- Recalcular el total.
- Mantener el nombre, unidad y precio snapshot.

La orden conservará la misma identidad mientras no haya sido facturada.

## Fase 5: historial y trazabilidad

### Implementado

- Entidad `OrderHistory` independiente y acumulativa.
- Registro de creación de la orden.
- Registro de actualización de datos de la orden.
- Registro de bloqueo y liberación de edición.
- Registro de componentes agregados, reemplazados y retirados.
- Usuario, fecha, acción y detalles JSON conservados en cada evento.
- Campos preparados para registrar estado anterior y nuevo estado.
- Vista `/ordenes/{id}/historial` accesible desde el listado de órdenes.
- El historial se conserva aunque la orden vuelva a editarse.

Registrar cada modificación de la orden:

- Usuario que ejecutó la acción.
- Fecha y hora.
- Acción realizada.
- Estado anterior y nuevo, cuando aplique.
- Componentes afectados.
- Cantidades y precios anteriores y nuevos.
- Observación o motivo, cuando corresponda.

El historial no deberá sobrescribirse cuando la orden sea editada nuevamente.

## Fase 6: datos y comprobantes de domicilio

### Implementado

- Carga de comprobantes JPG, PNG, WEBP o PDF.
- Tamaño máximo de 5 MB.
- Almacenamiento de archivos en MinIO.
- Reemplazo del comprobante antes de facturar.
- Registro de usuario, fecha, nombre, tipo y tamaño del archivo.
- Visualización protegida del comprobante desde la orden.

Agregar al flujo de domicilio:

- Carga de comprobante digital.
- Reemplazo del comprobante antes de facturar.
- Validación del tipo de archivo y tamaño.
- Registro del usuario que cargó el archivo.
- Datos iniciales de pago en efectivo o digital.
- Valor entregado y cálculo del cambio cuando aplique.

El valor definitivo del domicilio será responsabilidad del cajero y se
implementará en su fase correspondiente.

## Fase 7: impresión de comandas

### Simulación implementada

- Ruta `/ordenes/{id}/imprimir`.
- Vista con dos comprobantes separados: cocina y cliente.
- Formato visual de 80 mm.
- Total a pagar en la copia del cliente.
- Acceso desde la lista de órdenes.
- Apertura automática de la vista después de crear o editar una orden.
- El navegador permite imprimir o guardar la vista como PDF.

La conexión con la impresora térmica y el envío mediante el worker quedan
pendientes hasta definir el dispositivo físico.

Crear las solicitudes de impresión mediante el worker:

- Comanda para cocina.
- Copia para el cliente con el total.
- Reimpresión después de editar una orden pendiente.
- Registro de la solicitud y del usuario que la generó.
- Formato térmico de 80 mm.

## Fase 8: actualizaciones en tiempo real

### Base implementada

- Publicación Mercure para creación y edición de órdenes.
- Tema global `orders` para las vistas operativas.
- Tema individual `orders/{id}` preparado para vistas de detalle.
- La lista de órdenes se actualiza automáticamente al recibir un evento.
- Una falla temporal de Mercure no bloquea la creación o edición de órdenes.

Quedan pendientes los eventos de cocina, facturación, entrega y alertas sonoras.

Publicar con Mercure los cambios visibles para el mesero:

- Nueva orden enviada.
- Cambio de estado de cocina.
- Orden preparada, servida o empacada.
- Orden facturada.
- Orden entregada.
- Orden rechazada.
- Liberación o toma de un bloqueo.

Las alertas sonoras serán diferentes para cocina, mesero y cajero.

## Fase 9: permisos por grupos

Conectar cada acción con permisos configurables desde los grupos:

- Ver menú de venta.
- Crear órdenes.
- Editar órdenes.
- Agregar componentes.
- Quitar componentes.
- Reemplazar componentes.
- Cambiar mesa.
- Seleccionar cajero.
- Cargar comprobantes.
- Imprimir comandas.
- Reimprimir comandas.
- Ver órdenes pendientes.
- Marcar entrega en mesa.

El rol `ROLE_ADMIN` mantendrá acceso total y no dependerá de grupos. Los
usuarios con `ROLE_USER` dependerán exclusivamente de los permisos asignados a
sus grupos.

## Criterio para cerrar la implementación del mesero

El entorno del mesero estará completo cuando una persona pueda:

1. Iniciar sesión.
2. Crear una orden desde el menú.
3. Seleccionar tipo de pedido y cajero.
4. Editar sus componentes antes de facturar.
5. Enviar la orden a cocina.
6. Consultar el estado de la orden.
7. Cargar o reemplazar el comprobante de un domicilio.
8. Imprimir o reimprimir la comanda según sus permisos.
9. Consultar el historial de cambios permitido.
10. Recibir actualizaciones en tiempo real sin recargar la vista.
