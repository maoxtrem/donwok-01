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
- `user` utilizará el grupo de permisos para determinar su acceso.

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

### Experiencia del mesero en tablet

La interfaz del menú de venta estará optimizada para una tablet y requerirá la
menor cantidad posible de escritura manual.

El flujo principal será:

1. El mesero selecciona un producto individual o un combo.
2. El producto se agrega al pedido como un ítem.
3. El mesero puede editar cantidades o componentes del ítem.
4. El mesero confirma y envía el pedido.
5. El cajero valida el pago y continúa con la facturación.

La interfaz debe utilizar botones grandes, productos organizados por categorías,
acceso rápido a los combos, modificación sencilla de cantidades y un carrito
visible durante la creación del pedido.

## Escenario del mesero

El mesero tendrá únicamente las funciones necesarias para gestionar la venta y
dar seguimiento al pedido del cliente:

- Ver los productos disponibles.
- Crear productos compuestos o combos.
- Editar productos compuestos.
- Agregar componentes a un producto compuesto.
- Quitar componentes de un producto compuesto.
- Consultar el monto total que debe pagar el cliente.
- Consultar el estado del pedido.
- Consultar el estado del pedido en cocina para informar al cliente.
- Seleccionar el tipo de pedido:
  - Para llevar.
  - Para mesa.
  - Domicilio.

Cuando el pedido sea a domicilio, el mesero deberá registrar:

- Número de contacto del cliente.
- Dirección de entrega.
- Nombre del cliente.

En los pedidos para mesa o para llevar, el mesero no definirá el método de pago.

En los pedidos a domicilio existe una excepción: como el mesero atiende el
teléfono y toma los datos del cliente, también registrará el método de pago
informado por el cliente.

Si el pago del domicilio es digital, el mesero deberá subir el comprobante de
pago. El comprobante quedará asociado al pedido para que el cajero pueda
validarlo y realizar la facturación.

Si el pago del domicilio es en efectivo, el mesero deberá indicar si:

- El cliente paga el valor completo.
- El cliente necesita cambio.

Cuando se requiera cambio, se registrará el valor entregado por el cliente para
calcular cuánto debe devolverse.

El cajero será responsable de confirmar el valor del domicilio. Este valor se
sumará al total del pedido y quedará registrado en la factura.

El mesero no tendrá otras funciones administrativas dentro del sistema.

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

