# Prueba local: Nextcloud + ONLYOFFICE

Esta configuración se prueba con el contexto Docker local `default`. Los cambios pueden subirse después al servidor remoto.

## Versiones

- Nextcloud `35.0.2-apache`.
- ONLYOFFICE Docs Community `10.0.0`.
- Conector ONLYOFFICE para Nextcloud `10.2.1` (compatible con Nextcloud 35).

## Acceso

- Nextcloud: http://localhost/nextcloud/
- ONLYOFFICE Docs: http://localhost/onlyoffice/
- Acceso remoto previsto: http://docker-server.taileef54a.ts.net/nextcloud/

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
php occ config:system:set trusted_domains 6 --value=docker-server.taileef54a.ts.net
php occ config:system:set overwritewebroot --value=/nextcloud
php occ config:system:set overwrite.cli.url --value=http://docker-server.taileef54a.ts.net/nextcloud
```

La edición Community de ONLYOFFICE Docs es la edición AGPL para uso local. Tiene límites funcionales y de capacidad frente a las ediciones comerciales; la cantidad de usuarios simultáneos y algunas funciones/soporte dependen de la edición y versión. Para esta prueba se usa exclusivamente la edición Community.

## Respaldo

Respaldar siempre la base `nextcloud` y el volumen `nextcloud_data` juntos. No usar `down -v`, porque elimina volúmenes.

```bash
docker --context default exec donwok_database mysqldump -uroot -proot --single-transaction nextcloud > nextcloud.sql
docker --context default run --rm -v donwok-01_nextcloud_data:/data -v "$PWD":/backup alpine tar czf /backup/nextcloud-data.tar.gz -C /data .
```

Esta prueba usa HTTP: las credenciales y documentos no viajan cifrados. Para usar el hostname remoto con datos reales debe colocarse HTTPS delante del proxy.
