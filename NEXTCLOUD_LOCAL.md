# Prueba local: Nextcloud + ONLYOFFICE

Esta configuración se prueba con el contexto Docker local `default`. Los cambios pueden subirse después al servidor remoto.

## Versiones

- Nextcloud `35.0.2-apache`.
- ONLYOFFICE Docs Community `10.0.0`.
- Conector ONLYOFFICE para Nextcloud `10.2.1` (compatible con Nextcloud 35).

## Acceso

- Nextcloud: http://localhost/nextcloud/
- ONLYOFFICE Docs: http://localhost/onlyoffice/
- La URL pública se configura mediante `.env.nextcloud.local`; no está fija en Compose.

El servicio `nginx` de este Compose es el único proxy publicado; Nextcloud y ONLYOFFICE no exponen puertos directamente al host. Las rutas se agregan dentro del mismo bloque `server`, igual que MinIO y Mercure, mediante la red Docker `office`.

Usuario inicial de Nextcloud: `ncadmin`.

La contraseña inicial se consulta localmente desde el archivo excluido de Git:

```bash
awk -F= '$1 == "NEXTCLOUD_ADMIN_PASSWORD" {print $2}' .env.nextcloud.local
```

No se debe publicar ese archivo ni copiarlo al servidor remoto sin protegerlo.

## Operación

Iniciar la prueba, incluyendo solo sus dependencias locales:

```bash
docker --context default compose --env-file .env.nextcloud.local up -d database nextcloud-db-init nextcloud onlyoffice nextcloud-cron
```

Detener solo los servicios de esta prueba:

```bash
docker --context default compose --env-file .env.nextcloud.local stop nextcloud-cron onlyoffice nextcloud nextcloud-db-init
```

Consultar estado y logs sin imprimir secretos:

```bash
docker --context default compose --env-file .env.nextcloud.local ps
docker --context default logs --tail=100 donwok_nextcloud
docker --context default logs --tail=100 donwok_onlyoffice
```

## Usuarios y documentos

El primer usuario es administrador. Los usuarios adicionales se crean desde **Configuración → Usuarios** en Nextcloud. Los documentos se crean desde **Archivos → Nuevo**; el conector permite documentos, hojas de cálculo y presentaciones.

En una instalación existente, cambiar `NEXTCLOUD_TRUSTED_DOMAINS` en Compose no modifica automáticamente `config.php`. Después de subir estos cambios al servidor, ejecutar dentro del contenedor de Nextcloud:

```bash
php occ config:system:set trusted_domains 6 --value="$NEXTCLOUD_PUBLIC_HOST"
php occ config:system:set overwritehost --value="$NEXTCLOUD_PUBLIC_HOST"
php occ config:system:set overwriteprotocol --value="$NEXTCLOUD_PUBLIC_SCHEME"
php occ config:system:set overwritewebroot --value="$NEXTCLOUD_WEBROOT"
php occ config:system:set overwrite.cli.url --value="$NEXTCLOUD_PUBLIC_URL"
php occ config:app:set onlyoffice DocumentServerUrl --value="$ONLYOFFICE_PUBLIC_URL"
```

La variable `TRUSTED_PROXIES` debe contener la subred Docker donde está `nginx` (por defecto `172.16.0.0/12`). Si el remoto usa otra subred, ajustar ese valor en su `.env.nextcloud.local`; no usar el rango de clientes Tailscale como proxy confiable.

Redis se configura mediante `REDIS_HOST=redis` y se usa para el caché de bloqueo de archivos. El proxy agrega HSTS únicamente cuando la petición llega con esquema HTTPS; el acceso HTTP local no queda forzado a HTTPS.

Para mantenimiento en el remoto, programar la ventana a las 02:00 y ejecutar una vez las migraciones de tipos de archivo:

```bash
php occ maintenance:window --set-start 2
php occ maintenance:mimetype:update-db --repair-filecache
php occ maintenance:repair --include-expensive
```

La edición Community de ONLYOFFICE Docs es la edición AGPL para uso local. Tiene límites funcionales y de capacidad frente a las ediciones comerciales; la cantidad de usuarios simultáneos y algunas funciones/soporte dependen de la edición y versión. Para esta prueba se usa exclusivamente la edición Community.

## Respaldo

Respaldar siempre la base `nextcloud` y el volumen `nextcloud_data` juntos. No usar `down -v`, porque elimina volúmenes.

```bash
docker --context default exec donwok_database mysqldump -uroot -proot --single-transaction nextcloud > nextcloud.sql
docker --context default run --rm -v donwok-01_nextcloud_data:/data -v "$PWD":/backup alpine tar czf /backup/nextcloud-data.tar.gz -C /data .
```

Esta prueba usa HTTP: las credenciales y documentos no viajan cifrados. Para usar el hostname remoto con datos reales debe colocarse HTTPS delante del proxy.
