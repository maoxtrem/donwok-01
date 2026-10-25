# Fases de implementación de cocina

Este documento contiene la hoja de ruta técnica del entorno de cocina. La
definición funcional permanece en [`LOGICA_NEGOCIO.md`](LOGICA_NEGOCIO.md).

## Estado general

| Fase | Alcance | Estado |
|---|---|---|
| 1 | Vista móvil y cambio de estados | Completada |
| 2 | Vista TV sin interacción | Completada |
| 3 | Actualizaciones en tiempo real | TV integrada; eventos adicionales pendientes |
| 4 | Rechazo con motivo e historial | Parcial; reenvío no facturado implementado |
| 5 | Permisos por grupos | Permisos definidos; integración pendiente |
| 6 | Alertas sonoras | Pendiente |

## Fase 1: vista móvil y cambio de estados

La vista móvil permitirá consultar las órdenes que cocina debe procesar y
cambiar únicamente su estado operativo. No permitirá editar componentes,
facturar, anular ni marcar entregas.

Esta vista prioriza la interacción táctil y los botones de cambio de estado.
La presentación detallada para lectura en pantalla grande corresponde a la
vista TV de la fase 2.

### Estados permitidos

- `En preparación` → `Preparada`.
- `Preparada` → `Servido` para mesa.
- `Preparada` → `Empacada` para llevar o domicilio.
- `En preparación` o `Preparada` → `Rechazada`, con motivo obligatorio.

Las reglas de transición y el motivo de rechazo se validarán en el backend.
Cada cambio conservará el usuario y la fecha en el historial de la orden.

### Implementado

- Ruta móvil `/cocina`.
- Tarjetas móviles mínimas con número, tipo y estado de la orden.
- Endpoint `PATCH /api/kitchen/orders/{id}/status`.
- Transiciones según el tipo de pedido.
- Rechazo con motivo obligatorio.
- Registro del cambio en el historial.
- Validación de permisos `kitchen.read`, `kitchen.update_status` y
  `kitchen.reject`.
- Publicación del cambio mediante Mercure.

### Verificación

- La vista requiere autenticación.
- Solo muestra órdenes no entregadas.
- Los controles se adaptan a mesa, llevar y domicilio.
- El cambio de estado se realiza sin editar la orden.

## Fase 2: vista TV

### Implementado

- Ruta `/cocina/tv`.
- Vista sin botones ni controles de cambio de estado.
- Solo muestra órdenes en `En preparación`.
- Muestra cada orden completa, con sus compuestos y componentes agrupados.
- Conserva el detalle de medidas y cantidades de cada componente.
- Identifica claramente número, hora y tipo de pedido.
- Diseño oscuro y horizontal para pantalla de TV.

- Vista informativa sin controles de interacción.
- Solo muestra órdenes en `En preparación`.
- Muestra cada orden completa, con sus compuestos y componentes agrupados.
- Conserva el detalle de medidas y cantidades de cada componente.
- Identifica claramente número, hora y tipo de pedido.
- Oculta una orden cuando pasa a `Preparada`, `Servido` o `Empacada`.
- Diseño horizontal para pantalla de TV.

## Fase 3: tiempo real

### Implementado

- La TV se suscribe al tema Mercure global `orders`.
- Una nueva orden se muestra automáticamente.
- Una orden que cambia de estado se retira automáticamente de la TV.

- Suscripción Mercure para nuevas órdenes.
- Actualización al cambiar el estado.
- Eliminación automática de la TV cuando una orden deja de estar en preparación.

## Fase 4: rechazo e historial

- Motivo obligatorio y visible para mesero y administración.
- Reenvío de la misma orden después de ser corregida.
- Flujo especial si la orden ya fue facturada.

### Implementado parcialmente

- El motivo queda registrado en el historial.
- El motivo se muestra en la lista de órdenes rechazadas.
- Una orden rechazada y sin facturar puede abrirse para edición.
- Al guardar la corrección, vuelve automáticamente a `En preparación`.
- Una orden facturada continúa bloqueada para edición.

Queda pendiente el flujo administrativo para anular una orden facturada
rechazada y crear una nueva orden independiente.

## Fase 5: permisos por grupos

- Consultar comandas.
- Cambiar estado en cocina.
- Rechazar comanda con motivo.

## Fase 6: alertas sonoras

- Sonido de nueva orden para la TV.
- Sonido diferenciado para los cambios que deban recibir mesero o cajero.
