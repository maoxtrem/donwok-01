# Lógica de negocio

## Objetivo del proyecto

Pendiente de definir.

## Arquitectura

El sistema será un monolito.

El frontend realizará consultas al backend mediante JavaScript utilizando `fetch`.

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

## Módulos principales

Pendiente de definir.

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

