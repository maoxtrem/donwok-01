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

Cada grupo definirá a qué rutas o recursos puede acceder el usuario y qué
acciones CRUD puede ejecutar en cada uno. Para cada recurso se podrán habilitar
mediante checks los permisos de:

- Crear (`create`).
- Consultar o leer (`read`).
- Actualizar o editar (`update`).
- Eliminar (`delete`).

También podrán existir acciones operativas adicionales, como imprimir,
facturar, activar una caja, registrar egresos o cambiar un estado.

Podrán existir grupos con configuraciones diferentes para distintas
operaciones. Por ejemplo, se podrá crear un grupo `mesero` con un conjunto de
permisos predeterminado y también grupos especiales con acciones adicionales.

El nombre del grupo no determinará automáticamente sus capacidades. Cada
acción que pueda ejecutar un usuario deberá estar representada por un permiso
seleccionable en el grupo.

Todas las acciones implementadas en el sistema deberán poder marcarse como
habilitadas o deshabilitadas dentro de la configuración del grupo. El
administrador podrá crear grupos personalizados, definir las rutas a las que
pueden entrar y otorgar a sus usuarios cualquier combinación de permisos,
incluyendo las funciones normalmente asociadas al grupo de meseros.

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

Los permisos se asignarán directamente al usuario mediante uno o más grupos.
No existirá un perfil genérico intermedio. El usuario será responsable de la
autenticación y los grupos determinarán las acciones que puede ejecutar.

La operación de caja será opcional y tendrá una relación independiente:

```text
Usuario → Grupos de permisos
Usuario → Cajero opcional → Caja
```

El registro de cajero solo se creará cuando el usuario necesite operar una
caja. El nombre del grupo no concederá permisos automáticamente.

Cada acción que pueda realizar un mesero o un cajero será un permiso
independiente y aparecerá como un check dentro de la configuración del grupo.
Por ejemplo, crear órdenes, editar órdenes, imprimir, facturar, activar una
caja, registrar egresos o cerrar una caja serán acciones separadas. El usuario
solo podrá ejecutar las acciones que estén habilitadas en alguno de sus grupos.

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
4. El mesero selecciona el cajero responsable de recibir la orden.
5. El mesero confirma la orden al seleccionar `Ordenar`.
6. La orden aparece en la vista de comandas de cocina.
7. El sistema puede generar las impresiones físicas de apoyo.
8. La orden pasa al estado `En preparación`.
9. Cocina cambia la orden a `Preparada`.
10. Cocina cambia la orden a `Servido` si es para mesa o a `Empacada` si es
    para llevar o domicilio.
11. La facturación se ejecuta mediante un botón independiente.
12. La orden pasa a `Entregada` según las reglas de su tipo de pedido.

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
- Seleccionar un cajero autorizado antes de enviar la orden.

La orden quedará asociada al cajero seleccionado. El mesero no podrá enviarla
ni imprimirla si no ha seleccionado un cajero activo y autorizado.

Si el cajero asignado deja de estar disponible, un usuario perteneciente a un
grupo de administración podrá transferir únicamente las órdenes que todavía
no hayan sido facturadas a otro cajero activo. Las órdenes facturadas no podrán
cambiar de cajero. La transferencia conservará el cajero original, el nuevo
cajero, el usuario administrador que realizó la acción, la fecha, la hora y el
motivo.

El nuevo cajero podrá continuar el flujo normal de validación, facturación y
entrega de las órdenes transferidas.

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
teléfono y toma los datos del cliente, registrará la información que el cliente
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
órdenes que le hayan sido asignadas y que requieren validación de pago y
facturación.

Cuando el usuario asociado a un cajero inicie sesión, deberá activar
manualmente su sesión de cajero y la caja correspondiente desde su espacio de
trabajo. Iniciar sesión no activará automáticamente la caja. Mientras la caja
esté activa, el cajero aparecerá como disponible para que los meseros puedan
asignarle nuevas órdenes.

El cajero podrá desactivar manualmente la caja para impedir que los meseros le
envíen nuevas órdenes. La desactivación no iniciará ni confirmará el proceso de
cierre. El cajero podrá continuar procesando con normalidad las órdenes que ya
tenía asignadas.

El cierre será una acción independiente que el cajero realizará posteriormente.
Para cerrar la caja, todas las órdenes asignadas deberán estar facturadas y
entregadas.

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

La factura se imprimirá en una impresora térmica de 80 mm utilizando papel
continuo, con un formato optimizado para comprobantes de venta.

El cajero no modificará productos, combos ni componentes de la orden. Tampoco
anulará, corregirá ni editará órdenes ya facturadas. Esas acciones estarán
reservadas para usuarios pertenecientes a un grupo de administración.

### Numeración de órdenes y facturas

La orden y la factura tendrán numeraciones independientes.

- El número de orden comenzará en `1` y se reiniciará al comenzar cada día.
- El número de factura será continuo y comenzará en `#00000`.
- La numeración de facturas no se reiniciará diariamente.
- Cada registro de facturación conservará el número de orden relacionado.

Una factura podrá identificarse, por ejemplo, con su número continuo y el
número de la orden que la originó:

```text
Factura: #00025
Orden: 18
Fecha: día correspondiente
```

### Cajas asociadas al cajero

El sistema tendrá una tabla de cajas para registrar los movimientos de dinero
efectivo y electrónico. Podrán existir múltiples cajas y múltiples cajeros.

Cada registro de cajero estará asociado a un usuario y tendrá como máximo una
caja asignada. La asignación de cajas será configurable y la realizará la
administración.

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

### Traslados entre cajas

El cajero podrá trasladar dinero entre sus cajas asignadas cuando sea necesario.
Por ejemplo, podrá corregir un pago registrado inicialmente en la caja física
cuando en realidad fue recibido por un medio virtual.

Todo traslado deberá registrar:

- Caja de origen.
- Caja de destino.
- Valor trasladado.
- Motivo del traslado.
- Usuario autenticado que ejecutó la operación.
- Fecha y hora.

El traslado generará un movimiento de salida en la caja de origen y un
movimiento de entrada en la caja de destino. Ambos movimientos quedarán
relacionados y registrados en el libro mayor.

El usuario responsable se tomará automáticamente de la sesión autenticada. No
se permitirá seleccionar manualmente otro usuario como responsable del
traslado.

Las entradas ordinarias de una caja provendrán de ventas facturadas. Las
salidas ordinarias se realizarán mediante egresos. Los traslados entre cajas
serán la excepción autorizada para mover dinero entre cajas sin representar una
nueva venta ni un nuevo egreso.

Toda transacción entre cajas, incluyendo traslados desde cajas temporales,
traslados a cajas sin asignar y movimientos hacia una caja mayor o central,
quedará registrada en el libro mayor.

La ejecución de cualquier operación de caja dependerá de un permiso de acción
habilitado en el grupo del usuario autenticado. Esto incluye activar,
desactivar y cerrar cajas, registrar ingresos o egresos, trasladar dinero,
consultar movimientos y realizar arqueos. El grupo será quien otorgue o retire
cada permiso de forma independiente.

Un usuario puede pertenecer al grupo de cajeros sin tener una caja asignada.
En ese caso podrá iniciar sesión y consultar las órdenes pendientes, pero no
podrá registrar pagos, facturar ni marcar como entregadas las órdenes que
requieran facturación. El sistema mostrará que no tiene una caja asignada y no
utilizará automáticamente una caja diferente.

### Cierre de caja

El cajero no podrá realizar el cierre mientras existan órdenes pendientes
relacionadas con sus cajas o con su jornada de trabajo. Antes de cerrar, todas
las órdenes deberán estar facturadas y entregadas.

Si existe una orden sin facturar o sin entregar, el sistema bloqueará el cierre
y mostrará las órdenes pendientes que deben resolverse.

Durante el proceso de cierre, el cajero podrá registrar egresos de una caja.
Cada egreso deberá incluir:

- Valor.
- Motivo.
- Caja de origen.

Al guardar el egreso, el sistema generará e imprimirá un tiquete con esa
información como comprobante de salida de dinero.

Antes de confirmar el cierre, el sistema mostrará en pantalla el resumen de
los valores esperados por tipo de caja:

- Valor esperado en efectivo.
- Valor esperado en pagos digitales.

Para realizar el cierre, el cajero deberá registrar:

- El dinero físico contado.
- Los comprobantes de pagos digitales correspondientes a las órdenes
  electrónicas o mixtas.
- El monto total recibido por medios digitales.

En los pagos mixtos, el efectivo y el valor digital se conciliarán por
separado. El sistema comparará los valores registrados con los valores
esperados para determinar si existe diferencia.

El cálculo tendrá en cuenta los ingresos registrados, los egresos realizados y
los movimientos de cada caja. El resumen podrá imprimirse como comprobante del
cierre.

El cajero podrá confirmar el cierre aunque exista un descuadre. El sistema
deberá indicar claramente si la diferencia corresponde a un faltante o a un
sobrante, mostrar su valor y conservarla en el registro del cierre.

### Cajas temporales al cerrar

Cuando el cajero confirme el cierre, el saldo de cada caja asignada se
trasladará a una caja temporal independiente.

Si el cajero tiene tres cajas asignadas, el sistema podrá crear hasta tres
cajas temporales, una por cada caja cuyo saldo sea mayor que cero. Las cajas
sin saldo no generarán una caja temporal.

Las cajas temporales conservarán la relación con la caja de origen, el cierre y
el cajero responsable. No serán cajas asignables a usuarios ni estarán
disponibles para recibir nuevas órdenes.

El administrador podrá consultar las cajas temporales y trasladar su contenido
a cajas sin asignar destinadas para almacenamiento o consolidación. Cada
traslado conservará el origen, el destino, el valor, el administrador
responsable, la fecha, la hora y el motivo en el libro mayor.

El administrador podrá consultar y reimprimir un cierre de caja ya realizado.
La reimpresión no modificará el cierre original ni sus movimientos financieros.

### Libro mayor

El sistema tendrá un libro mayor para registrar todos los movimientos
financieros realizados por el cajero. Cada movimiento podrá ser un ingreso o un
egreso y deberá conservar:

- Tipo de movimiento.
- Valor.
- Caja involucrada.
- Usuario responsable.
- Fecha y hora.
- Motivo o concepto.
- Orden, factura o cierre relacionado, cuando corresponda.

Los ingresos por facturación, los egresos de caja, los pagos mixtos, los
traslados y cualquier otro movimiento financiero autorizado quedarán
registrados en el libro mayor. Estos registros no se eliminarán; cualquier
corrección deberá generar un movimiento de ajuste con su respectiva
trazabilidad.

El sistema contemplará dos tipos de dispositivos para el cajero:

- Computador de mesa: tendrá el espacio de trabajo completo con la lista de
  órdenes y las acciones disponibles para cada una.
- Tablet o dispositivo móvil: tendrá una ruta reducida para consultar las
  órdenes, cargar una fotografía del comprobante de pago y facturar.

La ruta para tablet o móvil estará optimizada para tomar o cargar rápidamente
la imagen del comprobante desde el dispositivo. Las acciones disponibles en
cualquiera de las interfaces dependerán de los permisos del grupo al que
pertenezca el usuario.

## Escenario de cocina

La cocina tendrá una vista de comandas en tiempo real. Cada orden aparecerá
con sus productos y componentes para que el personal pueda conocer lo que debe
prepararse.

La comanda mostrará claramente el tipo de pedido:

- Para mesa.
- Para llevar.
- Domicilio.
- Hora en que se creó la orden.

Los productos se mostrarán organizados por plantilla o ítem compuesto. Cada
grupo representará la forma en que debe prepararse y servirse el pedido. Por
ejemplo:

```text
Grupo: Arroz, papa y gaseosa
Grupo: Arroz, papa y costilla
```

De esta forma, el cocinero podrá identificar cada preparación completa y no
ver únicamente una lista separada de productos sin contexto.

Una misma plantilla o compuesto podrá repetirse dentro de una orden. La
comanda deberá indicar cada instancia o su cantidad, por ejemplo:

```text
1 - Arroz y papa
2 - Arroz y papa
```

Si los compuestos repetidos tienen modificaciones diferentes, se mostrarán
como instancias separadas para que cocina conozca exactamente qué debe preparar
en cada uno.

La cocina contará con dos vistas:

- Vista para TV: mostrará únicamente las comandas que estén en estado `En
  preparación`. Será una vista informativa sin interacción. Cuando el cocinero
  cambie una orden a `Preparada` o `Empacada`, dejará de mostrarse en la TV.
- Vista para dispositivo móvil: permitirá al cocinero ejecutar las acciones y
  cambiar el estado de preparación de las órdenes.

El cambio de estado realizado desde el dispositivo móvil se reflejará en tiempo
real en la vista de TV y en las demás vistas autorizadas. Cada acción de cocina
será un permiso independiente configurable dentro del grupo correspondiente.

La cocina solo podrá cambiar el estado de preparación de una orden. No podrá
editar productos, modificar componentes, facturar, cancelar ni entregar
órdenes.

Los estados que podrá establecer son:

- `Preparada`.
- `Empacada`.
- `Servido` para pedidos en mesa.
- `Rechazada`.

Cuando una orden sea marcada como `Rechazada`, el cocinero deberá especificar
el motivo. El rechazo no anulará ni cancelará la orden; únicamente informará que
no puede prepararse en las condiciones actuales, por ejemplo, por falta de un
producto o componente.

El mesero será responsable de revisar el motivo, modificar la orden cuando
corresponda y volver a enviarla a cocina para su preparación. La orden seguirá
siendo la misma y conservará su número e historial; no se creará una orden
nueva.

Cuando el mesero reenvíe una orden rechazada después de corregirla, su estado
volverá a `En preparación` y el cambio quedará registrado en el historial.

## Actualizaciones en tiempo real

Todas las actualizaciones visibles del sistema se transmitirán en tiempo real
mediante Mercure. Esto incluye, entre otros:

- Creación y edición de órdenes.
- Cambios de estado en cocina.
- Asignación o transferencia de órdenes a cajeros.
- Activación y desactivación de cajas.
- Facturación y entrega de órdenes.
- Movimientos y cierres de caja autorizados.

Cada vista solo recibirá los eventos correspondientes a sus rutas y permisos.
Las actualizaciones en tiempo real no reemplazarán la validación de permisos
en el backend.

### Impresión mediante worker

Las solicitudes de impresión no bloquearán la operación de los usuarios. Cada
impresión se enviará al worker para su procesamiento en segundo plano, con un
registro del documento, destino, usuario, fecha, estado y posibles errores.

El worker procesará las impresiones de facturas, órdenes y copias de apoyo.

### Chat interno

El sistema tendrá un chat interno minimalista para la comunicación entre los
usuarios autorizados.

El chat podrá funcionar en dos modalidades:

- Conversación individual entre dos usuarios.
- Conversación grupal entre varios usuarios.

El chat se mostrará como una burbuja pequeña, cerrada por defecto. El usuario
podrá abrirla únicamente cuando quiera iniciar o continuar una conversación y
cerrarla al terminar, sin cambiar de ruta ni alterar la vista de trabajo.

La ventana abierta deberá adaptarse a dispositivos móviles, ocupar el mínimo
espacio posible y no bloquear los controles ni el contenido principal de las
vistas de meseros, cajeros, cocina o administración.

La burbuja y las conversaciones no se mostrarán en la vista de TV de cocina,
porque esa pantalla estará reservada para las comandas en preparación.

### Alertas sonoras

La vista de TV de cocina emitirá una alerta sonora cuando llegue una nueva
orden para preparar.

Cuando cocina cambie una orden al estado `Servido`, el sistema enviará una
alerta sonora a las vistas autorizadas del mesero y del cajero para informarles
que el pedido está listo para continuar con el flujo correspondiente.

También existirá una acción manual para enviar una alerta sonora cuando sea
necesario llamar la atención del cajero o del mesero sobre una orden servida.
Esta acción estará protegida por un permiso independiente del grupo.

Los sonidos de cocina serán diferentes de los sonidos utilizados para meseros y
cajeros, de manera que cada área pueda identificar el tipo de alerta sin
confusión.

Si una orden ya facturada es rechazada por cocina, el motivo seguirá siendo
obligatorio. La orden no podrá ser editada por el mesero. Un usuario del grupo
de administración deberá escribir obligatoriamente el motivo de la anulación,
anular la operación facturada y crear una nueva orden para continuar el
proceso.

La nueva orden tendrá su propio número y será independiente de la orden
original. La orden original conservará el motivo del rechazo, el motivo de la
anulación y el usuario administrador responsable de la operación.

## Escenario del administrador

El administrador podrá realizar un arqueo de una caja o de un cajero sin
ejecutar el cierre.

El arqueo tendrá como objetivo comparar el valor esperado con el dinero real
disponible y detectar posibles diferencias. El arqueo deberá registrar:

- Caja o cajero auditado.
- Usuario administrador que realizó el arqueo.
- Fecha y hora.
- Valor esperado.
- Valor encontrado.
- Diferencia detectada.
- Observaciones.

El arqueo no desactivará la caja, no cerrará la jornada y no impedirá que el
cajero continúe procesando órdenes. Solo generará un registro de auditoría.

### Estados independientes de la orden

La orden tendrá dos estados separados: el estado operativo de preparación y
entrega, y el estado de facturación.

El estado operativo representa el avance de la orden.

Para pedidos en mesa:

```text
Pendiente → En preparación → Preparada → Servido → Entregada
```

Para pedidos para llevar o domicilio:

```text
Pendiente → En preparación → Preparada → Empacada → Entregada
```

El estado de facturación se manejará de forma independiente:

```text
Sin facturar → Facturada
```

La facturación se ejecutará mediante una acción independiente del flujo de
preparación y entrega. Por lo tanto, una orden puede estar facturada y todavía
no haber sido servida, empacada o entregada. También puede estar servida o
empacada y continuar sin facturar. La facturación no cambiará automáticamente
el estado operativo, ni la preparación cambiará automáticamente el estado de
facturación.

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

