# Lógica de negocio

## Objetivo del proyecto

El sistema será una plataforma de facturación y gestión de pedidos para un
restaurante.

Los módulos, actores y flujos específicos se definirán a continuación.

## Arquitectura

El sistema será un monolito.

Symfony se utilizará para el backend, incluyendo los controladores y el ORM.

Las vistas se construirán con Twig. Desde esas vistas, JavaScript realizará las
peticiones al backend mediante `fetch`, sin utilizar un frontend separado.

## Actores y roles

Symfony gestionará dos roles de usuario:

- `admin`
- `user`

Reglas de los roles:

- `admin` no pertenece a ningún grupo de permisos.
- `admin` puede acceder a todas las rutas existentes.
- `admin` tiene control total sobre la aplicación.
- Todos los usuarios operativos tendrán el rol `user`.
- El acceso y las acciones disponibles para cada usuario `user` serán
  determinados por el grupo o los grupos a los que pertenezca.

Las reglas específicas del rol `user` están pendientes de definir.

## Permisos

Existirá un grupo centralizado de permisos administrado por Symfony.

El grupo de permisos tendrá las siguientes responsabilidades:

- Controlar el acceso a las rutas de la aplicación.
- Definir si el usuario tiene permiso de lectura.
- Definir si el usuario tiene permiso de escritura, entendida como:
  - Crear registros.
  - Borrar registros.
  - Actualizar registros.
  - Editar registros.

La relación entre permisos, roles y rutas queda pendiente de definir.

Cada acción del sistema será un permiso independiente y se representará como
un check al crear o editar un grupo. Por ejemplo, un grupo podrá tener checks
para ver productos, crear órdenes, editar órdenes, imprimir comandas o
consultar estados.

Podrán existir grupos con configuraciones diferentes para distintas
operaciones. Por ejemplo, se podrá crear un grupo `mesero` con un conjunto de
permisos predeterminado y también grupos especiales con acciones adicionales.

El nombre del grupo no determinará automáticamente sus capacidades. Cada
acción que pueda ejecutar un usuario deberá estar representada por un permiso
seleccionable en el grupo.

Todas las acciones implementadas en el sistema deberán poder marcarse como
habilitadas o deshabilitadas dentro de la configuración del grupo. El
administrador podrá crear grupos personalizados y otorgar a sus usuarios
cualquier combinación de permisos, incluyendo las funciones normalmente
asociadas al grupo de meseros.

De esta forma, un usuario podrá recibir permisos de mesero u otras funciones
según el grupo al que pertenezca, sin que esas capacidades queden determinadas
de forma rígida por su rol.

## Autenticación y autorización

La autenticación y la obtención del rol se realizarán mediante el autenticador
de Symfony.

Symfony utilizará el rol del usuario para controlar el acceso general al sistema.

Los permisos específicos se gestionarán mediante una tabla de grupos. Cada
usuario podrá pertenecer a uno o más grupos, y los grupos determinarán los
permisos de las rutas y operaciones disponibles.

## Módulos principales

### Menú de venta

La primera ruta funcional del sistema será el menú de venta.

La URL y las operaciones disponibles en esta ruta están pendientes de definir.

### Lista de productos

La lista de productos será una ruta independiente del menú de venta.

La URL, los campos y las acciones disponibles en esta ruta están pendientes de definir.

### Productos individuales y combos

El sistema debe permitir vender productos individuales y productos compuestos o
combos preconfigurados.

Un combo puede estar formado por varios productos. Por ejemplo:

- Arroz: 300 gramos.
- Pollo frito: 1 unidad.
- Gaseosa: 1 unidad.

En la factura, el combo se mostrará como un solo ítem. Internamente, el sistema
conservará sus componentes para la preparación, el control de cantidades y el
inventario.

El mesero también podrá armar un pedido seleccionando productos individuales.

Los combos funcionarán como plantillas del menú. El mesero podrá modificar un
combo dentro del pedido del cliente sin alterar la configuración original del
combo para futuras ventas.

La configuración del combo y sus componentes se copiarán en el momento de la
venta para conservar el historial de la factura aunque posteriormente cambien
los productos, componentes o precios.

### Modelo de ítems compuestos en el pedido y la factura

Todo producto agregado a un pedido se representará como un ítem compuesto,
incluso cuando se trate de un solo producto.

Por ejemplo, una gaseosa agregada individualmente se representará así:

```text
Pedido o factura
└── Ítem compuesto
    └── Gaseosa
```

Un combo se representará como un ítem compuesto con varios componentes:

```text
Pedido o factura
└── Ítem compuesto
    ├── Arroz: 300 gramos
    ├── Pollo frito: 1 unidad
    └── Gaseosa: 1 unidad
```

Cada ítem compuesto será editable dentro del pedido. El mesero podrá agregar,
quitar, reemplazar componentes o modificar sus cantidades sin alterar el
producto del catálogo ni la plantilla original del combo.

Al agregar un producto o combo, el sistema copiará sus componentes y precios al
ítem del pedido. De esta forma, el pedido y la factura quedarán desacoplados del
catálogo y conservarán su información histórica aunque posteriormente cambien
los productos, combos o precios.

La estructura conceptual será:

- `Producto`: catálogo general.
- `Combo`: plantilla predefinida del menú.
- `PedidoItem`: copia editable agregada al pedido.
- `PedidoItemComponente`: productos que forman el ítem compuesto.
- `Factura`: versión final del pedido confirmada para facturación.

### Experiencia del mesero en tablet

La interfaz del menú de venta estará optimizada para una tablet y requerirá la
menor cantidad posible de escritura manual.

El flujo principal será:

1. El mesero selecciona un producto individual o un combo.
2. El producto se agrega al pedido como un ítem.
3. El mesero puede editar cantidades o componentes del ítem.
4. El mesero confirma la orden al seleccionar `Ordenar`.
5. La orden aparece en la vista de comandas de cocina.
6. El sistema puede generar las impresiones físicas de apoyo.
7. La orden pasa al estado `En preparación`.
8. Cuando termina la preparación, pasa a `Servido` si es para mesa o a
   `Empaquetado` si es para llevar.
9. El cajero valida el pago y puede facturar la orden según corresponda.

La interfaz debe utilizar botones grandes, productos organizados por categorías,
acceso rápido a los combos, modificación sencilla de cantidades y un carrito
visible durante la creación del pedido.

## Escenario del mesero

El mesero tendrá únicamente las funciones necesarias para gestionar la venta y
dar seguimiento al pedido del cliente:

- Ver los productos disponibles.
- Ver las órdenes pendientes.
- Utilizar los productos y combos existentes en el menú.
- Editar los ítems compuestos que ya fueron agregados a la orden.
- Intercambiar componentes dentro de los ítems de la orden.
- Quitar componentes de los ítems de la orden.
- Agregar componentes disponibles a los ítems de la orden.
- Editar las órdenes pendientes mientras no hayan sido facturadas.
- Consultar el monto total que debe pagar el cliente.
- Consultar el estado del pedido mientras se encuentre activo.
- Seleccionar el tipo de pedido:
  - Para llevar.
  - Para mesa.
  - Domicilio.

Para los pedidos en mesa, el número se registrará manualmente. No será
necesario crear ni administrar un catálogo de mesas.

Cuando se registre una orden para una mesa, el sistema comprobará si ya existe
una orden para ese número:

- Si existe una orden no facturada, el mesero podrá continuar editándola.
- Si la orden existente ya fue facturada, se creará una nueva orden para la
  misma mesa.

Si el cliente cambia de mesa, el número de mesa podrá modificarse únicamente
por usuarios cuyo grupo tenga el permiso correspondiente y solo mientras la
orden no haya sido facturada. Una vez facturada, la orden no podrá cambiar de
mesa.

### Bloqueo de edición entre meseros

Todos los meseros podrán consultar las órdenes existentes. Cuando un mesero
abra una orden pendiente para editarla, la orden quedará bloqueada para los
demás meseros. Otro mesero no podrá editar esa orden mientras el bloqueo esté
activo.

El bloqueo se liberará cuando el mesero guarde los cambios, cancele la edición
o cierre correctamente la orden. También deberá existir un mecanismo para
liberar bloqueos abandonados por desconexión o inactividad.

### Historial y trazabilidad de la orden

El sistema registrará el mesero que creó la orden y cada mesero que la edite.
Una orden podrá ser editada por diferentes meseros a lo largo de su ciclo de
vida, siempre que no haya sido facturada.

Cada edición y cambio de estado generará un registro histórico con:

- Usuario que realizó la acción.
- Fecha y hora.
- Estado anterior y estado nuevo.
- Componentes y cantidades modificados.
- Observaciones o motivo del cambio, cuando corresponda.

El historial conservará una copia de cada versión de la orden y una copia final
de cómo quedó antes de la facturación. Los cambios no sobrescribirán el
historial anterior.

Cuando el pedido sea a domicilio, el mesero deberá registrar:

- Número de contacto del cliente.
- Dirección de entrega.
- Nombre del cliente.

En los pedidos para mesa o para llevar, el mesero no definirá el método de pago.

En los pedidos a domicilio existe una excepción: como el mesero atiende el
t eléfono y toma los datos del cliente, registrará la información que el cliente
le comunique sobre el pago. El método de pago definitivo lo seleccionará el
cajero cuando reciba y valide el pago.

Si el pago del domicilio es digital, el comprobante podrá recibirse por
WhatsApp, o el mesero podrá tomarle una foto con la cámara de la tablet y
cargarla directamente al pedido. También podrá cargar una imagen existente.

El comprobante quedará asociado al pedido para que el cajero pueda validarlo
antes de realizar la facturación. El mesero podrá reemplazarlo tantas veces
como sea necesario antes de facturar, por ejemplo si la imagen está borrosa o
es incorrecta. Esta regla aplica tanto para domicilios solicitados por teléfono
como para domicilios solicitados presencialmente.

Si el pago del domicilio es en efectivo, el mesero deberá indicar si:

- El cliente paga el valor completo.
- El cliente necesita cambio.

Cuando se requiera cambio, se registrará el valor entregado por el cliente para
calcular cuánto debe devolverse.

El cajero será responsable de registrar o corregir el valor del domicilio antes
de facturar. Este valor se sumará al total del pedido y se mostrará en la
factura como un concepto separado.

El valor del domicilio se reflejará en la factura, pero no se contabilizará
como ingreso del restaurante. Se identificará como un valor externo o de
terceros.

El grupo de permisos predeterminado para meseros no podrá crear productos,
agregar productos al catálogo ni modificar las plantillas de combos. Sus
cambios estarán limitados a los ítems de la orden actual y a las órdenes
pendientes que todavía no hayan sido facturadas.

Las órdenes ya entregadas no estarán disponibles para la consulta normal del
grupo de meseros. Las órdenes para llevar o domicilio serán entregadas por
usuarios cuyo grupo tenga el permiso de entrega correspondiente, por lo que el
grupo de meseros predeterminado no podrá marcarlas como entregadas ni
modificarlas.

Estas restricciones son la configuración del grupo de meseros, no reglas
acopladas al rol `user`. Un grupo especial podrá recibir permisos adicionales
si la operación lo requiere.

### Vista de cocina e impresión de órdenes

La cocina tendrá una vista propia de comandas. Esta vista será el medio
principal para recibir, consultar y actualizar las órdenes que deben prepararse.

La impresión física será un apoyo operativo y contará con dos formatos:

- Copia de apoyo para cotejar los productos y componentes preparados.
- Orden para el cliente, con el detalle del pedido y el valor total a pagar.

Cuando el mesero crea una orden y selecciona imprimir, el sistema podrá
generar las copias físicas. Estas podrán utilizarse para cotejar la orden y
marcarla cuando se entregue. Si edita una orden pendiente, podrá volver a
imprimir la información actualizada.

El mesero no tendrá otras funciones administrativas dentro del sistema.

## Escenario del cajero

El cajero tendrá un espacio de trabajo propio para consultar y gestionar las
órdenes que requieren validación de pago y facturación.

Las funciones principales del cajero serán:

- Consultar las órdenes pendientes de facturación.
- Revisar el detalle y el total de la orden.
- Registrar o corregir el valor del domicilio antes de facturar.
- Seleccionar el método de pago al recibir el pago.
- Validar el pago y los comprobantes recibidos.
- Registrar el valor del domicilio cuando corresponda.
- Facturar la orden.
- Imprimir o entregar la factura.
- Marcar como entregadas las órdenes para llevar o domicilio después de
  facturarlas.

El cajero no modificará productos, combos ni componentes de la orden. Tampoco
anulará, corregirá ni editará órdenes ya facturadas. Esas acciones estarán
reservadas para usuarios pertenecientes a un grupo de administración.

### Cajas asociadas al cajero

El sistema tendrá una tabla de cajas para registrar los movimientos de dinero
efectivo y electrónico. Podrán existir múltiples cajas y múltiples cajeros.

Cada cajero podrá tener una o varias cajas asignadas. La asignación de cajas
será configurable y no estará limitada a una cantidad fija.

Una misma caja no podrá estar asignada simultáneamente a dos cajeros. Si una
caja debe cambiar de responsable, la administración deberá retirar la
asignación anterior antes de asignarla al nuevo cajero.

Las cajas podrán representar diferentes destinos, por ejemplo:

- Caja de efectivo.
- Caja electrónica.
- Caja mayor o caja central.

Para la facturación existirán dos cajas predeterminadas:

- Caja predeterminada para efectivo.
- Caja predeterminada para pagos digitales.

Aunque un cajero tenga asignadas una tercera caja o más cajas adicionales, los
pagos de las facturas se distribuirán inicialmente entre las dos cajas
predeterminadas.

El usuario podrá seleccionar una caja adicional autorizada cuando necesite
recibir un pago específico en ella. Esta selección deberá quedar registrada en
el movimiento de pago y dependerá del permiso correspondiente del grupo.

Cuando un cliente pague una parte en efectivo y otra parte digital, la orden
registrará dos movimientos de pago: el valor en efectivo se enviará a la caja
predeterminada de efectivo y el valor digital a la caja predeterminada digital.

Las cajas adicionales también podrán utilizarse para otros movimientos
autorizados, como traslados a una caja mayor o central, según la configuración
administrativa.

La asignación de cajas a los cajeros y los destinos permitidos serán definidos
por la administración.

El sistema contemplará dos tipos de dispositivos para el cajero:

- Computador de mesa: tendrá el espacio de trabajo completo con la lista de
  órdenes y las acciones disponibles para cada una.
- Tablet o dispositivo móvil: tendrá una ruta reducida para consultar las
  órdenes, cargar una fotografía del comprobante de pago y facturar.

La ruta para tablet o móvil estará optimizada para tomar o cargar rápidamente
la imagen del comprobante desde el dispositivo. Las acciones disponibles en
cualquiera de las interfaces dependerán de los permisos del grupo al que
pertenezca el usuario.

### Estados independientes de la orden

La orden tendrá dos estados separados: el estado operativo de preparación y
entrega, y el estado de facturación.

El estado operativo representa el avance de la orden:

```text
Pendiente → En preparación → Servido
```

Para pedidos para llevar o domicilio, el último estado de preparación será:

```text
Pendiente → En preparación → Empaquetado
```

El estado de facturación se manejará de forma independiente:

```text
Sin facturar → Facturada
```

Por lo tanto, una orden puede estar facturada y todavía no haber sido servida
o empaquetada. También puede estar servida o empaquetada y continuar sin
facturar. La facturación no cambiará automáticamente el estado de preparación,
ni la preparación cambiará automáticamente el estado de facturación.

### Entrega según el tipo de pedido

La acción de marcar una orden como entregada dependerá del tipo de pedido:

- Para mesa: un usuario cuyo grupo tenga el permiso de entrega en mesa podrá
  marcar la orden como entregada cuando el pedido haya sido servido. Después,
  el cliente podrá acercarse a pagar.
- Para llevar o domicilio: un usuario cuyo grupo tenga el permiso de entrega
  externa podrá marcar la orden como entregada y solo podrá hacerlo después de
  que la orden haya sido facturada.

## Entidades y relaciones

Pendiente de definir.

## Reglas de negocio

Pendiente de definir.

## Flujos principales

Pendiente de definir.

## Estados y transiciones

Pendiente de definir.

## Notificaciones y eventos

Pendiente de definir.

## Integraciones

Pendiente de definir.

