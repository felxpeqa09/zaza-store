# ZAZA STORE

Tienda de ropa urbana masculina en Ibagué. La tienda incluye escaparate, carrito, favoritos, pedidos y gestión de catálogo en Filament.

## Requisitos

PHP 8.3 o superior con `pdo_pgsql`, `intl`, `mbstring`, `xml` y `zip`, Composer 2, Node.js 24 y PostgreSQL. Versiones de los archivos de bloqueo de esta entrega: Laravel 13.43.0, Breeze 2.4.2, Filament 5.12.0, Bootstrap 5.3 y Vite 8. La autenticación usa los controladores y rutas de Breeze con vistas Blade y Bootstrap. Filament publica sus propios estilos.

## Arranque

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

En el proyecto Supabase dedicado a la tienda, abre **Connect → Session pooler** y configura `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT=5432`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`. El usuario del pooler suele ser `postgres.<project-ref>`. Conserva `DB_SSLMODE=require`. El pooler de sesión permite conectar si el servidor no dispone de IPv6 para la conexión directa. Para una conexión opcional en modo transacción, usa `DB_PORT=6543` y `DB_EMULATE_PREPARES=true`. Ejecuta las migraciones mediante el pooler de sesión. Consulta la [guía oficial de conexión de Supabase](https://supabase.com/docs/guides/database/connecting-to-postgres).

Configura `ADMIN_NAME`, `ADMIN_EMAIL` y `ADMIN_PASSWORD` para crear el administrador. El seeder conserva las credenciales de un administrador existente y rechaza un correo que ya pertenezca a un cliente. Si configuras el administrador en una instalación existente, ejecuta `php artisan db:seed --class=AdminSeeder`.

```sh
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Abre `/` para visitar el escaparate, `/registro` para crear una cuenta de cliente y `/admin` para entrar con el administrador. Registra una cuenta de cliente e intenta abrir `/admin` para verificar que recibe un 403. Las imágenes del seeder usan un gráfico local reemplazable y las tallas de ejemplo para zapatos van de 38 a 43.

## Arquitectura y permisos

Una ruta entrega una vista Blade o invoca un controlador. Los Form Requests validan la entrada y los modelos Eloquent representan los datos. Los servicios de mantenimiento ejecutan el trabajo compartido. Filament usa la misma tabla `users`, el guard web y `User::canAccessPanel()`. Las políticas de productos, banners y pedidos se resuelven mediante Laravel y se aplican en Filament y en las rutas de pedidos.

La migración de RLS y el comando idempotente `php artisan db:enable-rls` cubren las tablas de `public`. Comprueban que el usuario conectado sea el propietario de las tablas, que ninguna fuerce RLS al propietario y que el esquema dedicado esté libre de políticas. Laravel usa su conexión de servidor con el rol propietario. [RLS en Supabase](https://supabase.com/docs/guides/database/postgres/row-level-security)

## Pruebas

Crea una base PostgreSQL desechable de nombre terminado en `_test`. Las pruebas reinician su esquema. Deja `DB_URL` vacío y proporciona las credenciales de pruebas mediante variables de entorno. Usa `DB_SSLMODE=disable` únicamente si tu PostgreSQL local de pruebas usa un socket o carece de TLS.

```sh
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=zaza_test \
  DB_USERNAME=zaza_test DB_SSLMODE=disable php artisan test
```

Con la configuración predeterminada de PHPUnit solo se ejecutan las pruebas que no necesitan PostgreSQL.

## Mantenimiento

Configura `HEALTH_TOKEN` con un secreto aleatorio y los secrets `HEALTH_TOKEN` y `APP_URL` en GitHub Actions. El workflow consulta `GET /health/db` cada ocho horas. En una terminal separada puedes iniciar `php artisan queue:work` y `php artisan schedule:work`. En producción usa Supervisor para el worker y un cron con `* * * * * php artisan schedule:run` desde el directorio del proyecto. El scheduler ejecuta `php artisan supabase:keepalive` cada seis horas.

Los proyectos Free están sujetos a cuotas y pueden pausarse por baja actividad. Los pings no garantizan disponibilidad. Programa `scripts/backup-db.sh` con `ZAZA_BACKUP_DIR`, `PGHOST`, `PGPORT`, `PGUSER`, `PGDATABASE` y un archivo `PGPASSFILE` con permisos 0600. El script ejecuta `pg_dump` en formato custom para el esquema de la tienda. [Planes de Supabase](https://supabase.com/pricing)

En producción usa HTTPS, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` y Nginx con raíz en `public/` y PHP-FPM. Después del despliegue ejecuta `php artisan filament:upgrade`, `php artisan config:cache`, `php artisan route:cache` y `php artisan view:cache`.

## Catálogo público

Abre `/ropa`, `/zapatos` o `/accesorios` para filtrar por subcategoría, precio o talla disponible. La paginación conserva los filtros. En `/producto/{slug}` se pueden explorar las fotos, colores, tallas y stock de cada variante. `/buscar?q=camisa` devuelve una página de resultados y ofrece JSON con el encabezado `Accept: application/json`, el mismo endpoint que usa la búsqueda de la barra principal.

El catálogo muestra el banner activo de menor orden. Las rutas de imágenes en base de datos son relativas a `CATALOG_DISK`, que usa el disco `public` por defecto. Las fotos editoriales del inicio son ilustrativas y los productos de ejemplo usan la imagen reemplazable del seeder.

## Carrito y favoritos

Agrega un artículo desde su ficha y usa el carrito lateral para cambiar la cantidad o retirarlo. El servidor valida el stock y calcula los importes con los precios vigentes. Al iniciar sesión, el carrito de invitado se combina con el de la cuenta por variante. Los ajustes de disponibilidad aparecen como avisos. La tabla `cart_merges` registra las fusiones para que un reintento de sesión no vuelva a sumar unidades.

Para probar favoritos, inicia sesión, pulsa el corazón junto a un producto y abre `/favoritos`. Las acciones del carrito y de favoritos usan el middleware web, con token CSRF en las escrituras. Mantén un almacén de caché que soporte bloqueos atómicos para serializar solicitudes simultáneas de la misma sesión. El proyecto usa `database` por defecto.

El carrito comprueba disponibilidad. La reserva de inventario se crea al iniciar el pago desde el checkout.


## Cuenta, recuperación y reCAPTCHA

El registro y el inicio de sesión están en `/registro` e `/iniciar-sesion`. La cuenta se consulta en `/cuenta` y se edita en `/cuenta/configuracion`. La foto de perfil se guarda en el disco local privado y se sirve únicamente a su dueño. Los datos de configuración y las contraseñas se actualizan en formularios distintos.

Configura `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` y el esquema SMTP que corresponda. Ajusta `APP_URL` al dominio público de la tienda para generar enlaces válidos. Ejecuta `php artisan queue:work` con `QUEUE_CONNECTION=database` para entregar los correos de verificación y recuperación. Evita el mailer `log` para estos correos porque incluyen enlaces de acceso. Los enlaces de recuperación vencen a los 60 minutos. Para enviar uno, abre `/olvide-mi-contrasena`. El formulario muestra el mismo mensaje para correos registrados y desconocidos.

La protección opcional del inicio de sesión público usa reCAPTCHA v2 con casilla. Registra el dominio con Google y configura `RECAPTCHA_ENABLED=true`, `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY` y, si difiere del host de `APP_URL`, `RECAPTCHA_HOSTNAME`. La clave de sitio se usa en el navegador. El secreto se usa en el servidor para comprobar la respuesta y el dominio. [Mostrar reCAPTCHA v2](https://developers.google.com/recaptcha/docs/display) y [verificar la respuesta](https://developers.google.com/recaptcha/docs/verify).

## Checkout Pro Colombia

En un entorno de pruebas con datos descartables, configura `APP_URL` con un dominio HTTPS público, `MERCADOPAGO_ENV=test`, el Access Token de prueba de la aplicación colombiana en `MERCADOPAGO_ACCESS_TOKEN` y el secreto de Webhooks en `MERCADOPAGO_WEBHOOK_SECRET`. El otro valor admitido para el entorno es `production`, que requiere las credenciales de producción correspondientes. El token y el secreto pertenecen al servidor.

En **Tus integraciones → Webhooks**, configura `https://TU-DOMINIO/webhooks/mercadopago` y el evento **Pagos**. Laravel verifica la firma con el `data.id` de la URL, `x-request-id` y `ts`, y consulta el pago y la cuenta asociada al Access Token por la API. Los parámetros de retorno de Checkout Pro solo llevan al usuario al resultado de su pedido.

1. Aplica las migraciones con `php artisan migrate`, inicia `php artisan queue:work database` y ejecuta el scheduler.
2. Crea una cuenta de comprador en la tienda y verifica su correo. Agrega, por ejemplo, una Camisa blanca Emotions y abre `/checkout`.
3. Con una cuenta de comprador de prueba de Mercado Pago en una ventana de incógnito, pulsa «Pagar con Mercado Pago». Comprueba que Checkout Pro muestre **190.000 COP** por una unidad y usa una tarjeta de prueba y el resultado aprobado según la guía oficial.
4. Comprueba en el panel de desarrolladores que el webhook firmado recibió HTTP 200. Abre el resultado del pedido. Si la notificación aún no se procesó, verás «Estamos verificando tu pago». Después de la acreditación, el resultado muestra «Pago confirmado» y el worker procesa el único correo encolado.
5. Para probar el rechazo o un pago pendiente usa los escenarios de la guía de Mercado Pago. Puedes ejecutar `php artisan orders:release-expired` para conciliar pedidos cuyo plazo de reserva ya venció.

Los precios del catálogo ya incluyen IVA y se envían a la preferencia en pesos COP enteros. El proveedor recibe el mismo total que calcula Laravel. El checkout es alojado y la tienda guarda solo datos necesarios de conciliación. Cada preferencia expira con la reserva de 30 minutos. Reintentar la creación de una preferencia conserva el pedido, sus datos de envío y su reserva. Si el proveedor procesó la primera solicitud pero su respuesta se perdió, puede haber más de una preferencia asociada a la misma referencia. Solo se acredita un pago a efectos del pedido.

El comando de expiración consulta por referencia y vuelve a revisar intentos conocidos. Si Mercado Pago está indisponible, conserva el stock reservado y reintenta en la siguiente ejecución del scheduler. Los medios de confirmación diferida pueden aprobarse después de que se libere el stock. En ese caso `payments.raw_payload.conciliacion` contiene `reserva_liberada`. Una segunda aprobación para un pedido pagado se identifica con `aprobacion_adicional`. Localiza el pedido por su referencia y el cobro por `provider_payment_id`, contrasta el movimiento en Mercado Pago y tramita el reembolso o acuerda una solución con el comprador antes de cualquier despacho. El pedido expirado no se reactiva y el stock no se descuenta de nuevo.

El editor conserva las variantes de pedidos pendientes y adquiere los bloqueos de producto y variante en ese orden, por ID ascendente. El stock de variantes representa unidades disponibles después de descontar reservas pendientes.

Fuentes de Mercado Pago Colombia:

- [Crear preferencia de Checkout Pro](https://www.mercadopago.com.co/developers/es/reference/online-payments/checkout-pro-preferences/create-preference/post)
- [URLs de retorno](https://www.mercadopago.com.co/developers/es/docs/checkout-pro-preferences/configure-back-urls)
- [Vigencia de la preferencia](https://www.mercadopago.com.co/developers/es/docs/checkout-pro-preferences/additional-settings/term-of-preference)
- [Firma y notificaciones de pago](https://www.mercadopago.com.co/developers/es/docs/checkout-pro-preferences/payment-notifications)
- [Credenciales y consulta de la cuenta receptora](https://www.mercadopago.com.co/developers/es/docs/checkout-pro-orders/resources/credentials)
- [Consultar intentos por referencia](https://www.mercadopago.com.co/developers/es/reference/online-payments/checkout-api-payments/search-payments/get)
- [Cuentas de prueba](https://www.mercadopago.com.co/developers/es/docs/checkout-pro-preferences/test-accounts) y [compras de prueba](https://www.mercadopago.com.co/developers/es/docs/checkout-pro-preferences/integration-test/test-purchases)


## Administrar el catálogo

1. Inicia sesión en `/admin`. Abre **Productos → Nuevo producto**. Ponle un nombre, verifica el slug, elige categoría y registra el precio en pesos enteros.
2. Sube y ordena las imágenes. Si necesitas colores, agrégalos antes de crear las variantes. En cada variante elige talla y stock disponible. Marca **Activo** y guarda.
3. Busca el producto por nombre o slug en la tabla y abre **Ver**. Comprueba la galería y el stock en el panel y la ficha pública en `/producto/{slug}`.
4. En **Editar**, cambia el precio y el stock disponible. Si cambió el stock mientras el formulario estaba abierto, recarga antes de aplicar otra cantidad. Las variantes reservadas conservan su talla y color.
5. En **Eliminar**, confirma la eliminación lógica. Verifica que el producto desaparezca del catálogo y la búsqueda. Los pedidos previos conservan sus datos.
6. En **Banners** puedes subir una imagen, editar texto y enlace, y establecer el orden y la visibilidad. El escaparate muestra el banner activo de menor orden.

Los textos provisionales de privacidad, devoluciones y cookies están en `resources/views/pages`. Configura, si lo deseas, la URL HTTPS de mapa embebido con `ZAZA_MAP_EMBED_URL`. La tienda expone `/robots.txt` y `/sitemap.xml` a partir de `APP_URL`.

## Imágenes en Supabase Storage

En desarrollo usa `CATALOG_DISK=public` y ejecuta `php artisan storage:link`. En Supabase crea un bucket público para imágenes de catálogo. Su lectura pública funciona por la URL del bucket. Deja las operaciones de escritura en el servidor, mediante claves S3 guardadas en el entorno de Laravel. No otorgues escritura a roles públicos del Data API de Storage.

En el panel **Storage → S3 Settings**, copia el endpoint, la región y las claves de acceso S3. Configura:

```dotenv
CATALOG_DISK=supabase
SUPABASE_STORAGE_ACCESS_KEY_ID=
SUPABASE_STORAGE_SECRET_ACCESS_KEY=
SUPABASE_STORAGE_REGION=
SUPABASE_STORAGE_BUCKET=catalogo
SUPABASE_STORAGE_ENDPOINT=https://<project-ref>.storage.supabase.co/storage/v1/s3
SUPABASE_STORAGE_PUBLIC_URL=https://<project-ref>.supabase.co/storage/v1/object/public/catalogo
```

El adaptador Flysystem firma las escrituras del servidor. La URL pública de lectura no incluye credenciales. Conserva en Supabase las imágenes cuyo path ya esté registrado si cambias el disco de un entorno con catálogo existente. Las imágenes del seeder usan el gráfico local. Los avatares siguen en el disco privado de Laravel. [Autenticación S3](https://supabase.com/docs/guides/storage/s3/authentication) y [compatibilidad S3](https://supabase.com/docs/guides/storage/s3/compatibility).

## Despliegue con Nginx

Los ejemplos en `deploy/` cubren Nginx, PHP-FPM, Supervisor y cron. Adapta dominio, certificados HTTPS, rutas, usuario y socket de PHP-FPM. Configura en el servidor las variables de Supabase, Mercado Pago, correo, `APP_ENV=production`, `APP_DEBUG=false`, la URL pública HTTPS y `SESSION_SECURE_COOKIE=true`. Conserva `APP_KEY` y el directorio de almacenamiento privado entre releases. La aplicación y el worker necesitan permisos de escritura en `storage` y `bootstrap/cache`.

Para preparar un release:

```sh
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan filament:upgrade
php artisan storage:link
php artisan optimize
php artisan queue:restart
```

En la primera instalación configura las variables `ADMIN_*` y ejecuta `php artisan db:seed --force` para cargar el catálogo de ejemplo y crear el administrador. Instala `deploy/nginx.conf` en la configuración de Nginx y valida con `nginx -t` antes de recargar. Registra `deploy/supervisor.conf`, ejecuta `supervisorctl reread` y `supervisorctl update`, y confirma que el worker está en ejecución. Instala el ejemplo de cron como archivo de `/etc/cron.d`.

Comprueba en el dominio público el inicio, el acceso administrativo, las imágenes y el flujo HTTPS de prueba de Mercado Pago. Verifica la salud de PostgreSQL con el token configurado y confirma que el scheduler y el worker están activos.
