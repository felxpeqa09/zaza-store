# ROL Y OBJETIVO

Eres un ingeniero full-stack senior especialista en Laravel. Construye ZAZA STORE, un e-commerce de ropa masculina urbana para una tienda física en Ibagué, Colombia.

La tienda tiene dos tipos de cuenta, usuario y administrador. Los clientes compran mediante Mercado Pago Checkout Pro Colombia. Los administradores gestionan el catálogo desde un panel Filament con CRUD completo de productos.

Trabaja por fases. Empieza únicamente por la Fase 1. Al terminar cada fase, ejecuta `php artisan migrate`, `php artisan test` y `npm run build`. Informa qué implementaste, cómo probarlo, qué verificaciones pasaron y qué queda pendiente. Espera mi confirmación antes de iniciar la siguiente fase.

No inventes funcionalidades fuera de este documento. Antes de instalar paquetes, comprueba la compatibilidad entre PHP, Laravel, Breeze, Filament y sus dependencias. Antes de integrar Mercado Pago o Supabase, consulta su documentación oficial vigente. Para Mercado Pago, utiliza la documentación de Checkout Pro correspondiente a Colombia. Si falta una credencial externa, identifica la prueba pendiente y distingue los resultados simulados de los verificados con el proveedor.

# STACK OBLIGATORIO

- PHP 8.3 o superior. Usa la última versión estable de Laravel compatible con el conjunto solicitado. Documenta las versiones elegidas.
- PostgreSQL alojado en un proyecto existente de Supabase. Conexión desde Laravel mediante `pgsql`.
- Frontend público con Blade, Vite, Bootstrap 5.3, SCSS propio y Alpine.js. Sin React ni Vue.
- Autenticación con Laravel Breeze en Blade y vistas personalizadas. Integra sus formularios con el diseño Bootstrap de la tienda.
- Panel administrativo con la última versión estable compatible de Filament. Mantén sus estilos independientes de los del escaparate.
- Pagos en COP mediante Mercado Pago Checkout Pro, detrás de la interfaz `PaymentGateway`.
- Pruebas con Pest o PHPUnit. Ejecuta las pruebas específicas de PostgreSQL sobre una base PostgreSQL de pruebas aislada.
- Configuración mediante `.env` y archivos `config/*.php`. Entrega `.env.example` sin secretos.
- Idioma español, zona horaria `America/Bogota` y moneda COP.
- Correo y tareas en cola con driver `database`. Sesión y caché en base de datos.
- Producción con Nginx, PHP-FPM, Supervisor para el worker y cron para el scheduler.

# CUENTAS, ROLES Y PERMISOS

Utiliza una sola tabla `users`, el modelo `User` y el mismo sistema de autenticación para la tienda y el panel.

| Acción                                                          | Invitado                | Usuario | Administrador |
| --------------------------------------------------------------- | ----------------------- | ------- | ------------- |
| Ver catálogo público, buscar y usar carrito                     | Sí                      | Sí      | Sí            |
| Guardar favoritos, finalizar compra y consultar pedidos propios | Requiere iniciar sesión | Sí      | Sí            |
| Editar el perfil propio                                         | Requiere iniciar sesión | Sí      | Sí            |
| Entrar a `/admin`                                               | Requiere iniciar sesión | No      | Sí            |
| Listar todos los productos y consultar su ficha administrativa  | No                      | No      | Sí            |
| Crear, editar o eliminar productos                              | No                      | No      | Sí            |
| Administrar el banner del catálogo                              | No                      | No      | Sí            |
| Consultar un pedido de otro usuario                             | No                      | No      | Sí            |

- Representa los dos roles mediante `users.is_admin`, booleano, obligatorio y con valor predeterminado `false`. Añade cast booleano en `User`.
- Todo registro público crea una cuenta de usuario. El registro y la edición de perfil aceptan únicamente campos permitidos. Excluye `is_admin` de `$fillable` y de los datos que esas operaciones pueden persistir.
- Crea el administrador inicial con un seeder que lea `ADMIN_EMAIL`, `ADMIN_NAME` y `ADMIN_PASSWORD`. Guarda el hash de la contraseña. Ejecutar nuevamente el seeder no debe restablecer la contraseña ni promover una cuenta pública existente por coincidencia de correo. Si hay conflicto, informa del problema.
- Protege el acceso a Filament mediante el mecanismo de autorización de panel de la versión instalada. Aplica `ProductPolicy` en el servidor para listar, ver, crear, actualizar y eliminar. Protege también las imágenes, variantes, acciones relacionadas y el recurso de banners.
- La navegación puede mostrar «Panel de administración» a un administrador autenticado. La autorización debe seguir funcionando al acceder directamente a una URL o ejecutar una acción del panel.

# ARQUITECTURA

- `app/Models`: modelos Eloquent con relaciones tipadas, casts, scopes `activo`, `destacado`, `porCategoria` y `buscar`, y accessor de precio formateado. Conserva los valores monetarios originales para cálculos.
- `resources/views`: componentes Blade reutilizables. Entrega los datos preparados desde el controlador. No hagas consultas, cálculos de negocio ni llamadas a servicios desde Blade.
- `app/Http/Controllers`: controladores delgados del escaparate. Incluye `HomeController`, `CatalogController`, `ProductController`, `CartController`, `WishlistController`, `CheckoutController`, `OrderController`, `AccountController`, `PageController` y `MercadoPagoWebhookController`. Usa Form Requests cuando haya entradas que validar. Procura mantener cada método en unas 30 líneas o menos.
- `app/Filament`: recursos, formularios y acciones del panel. Implementa aquí el CRUD administrativo. `ProductController` se ocupa de la ficha pública.
- `app/Policies`: autorización sobre productos, banners y pedidos. `OrderPolicy` permite consultar un pedido a su dueño o a un administrador.
- `app/Services`: `CartService`, `CheckoutService` y `StockReservationService` para lógica transaccional compartida.
- `app/Payments`: interfaz `PaymentGateway` y adaptador `MercadoPagoGateway`. Aísla aquí las particularidades del proveedor.
- Rutas con nombre en `routes/web.php`, agrupadas por middleware `auth` y `verified` donde corresponda. Carrito, favoritos y búsqueda usan endpoints JSON internos bajo el middleware web, con CSRF en las operaciones que modifican datos.
- Documenta en el README el flujo Ruta → Controlador → Modelo → Vista y cómo Filament reutiliza los modelos y las políticas.

# BASE DE DATOS, SUPABASE Y ALMACENAMIENTO

- Configura `pgsql` con `sslmode=require`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`. Obtén del proyecto la cadena de conexión y el usuario del pooler, habitualmente `postgres.<project-ref>`.
- Usa Supavisor en modo sesión, puerto 5432, para migraciones y como conexión predeterminada de la aplicación. Si se habilita el modo transacción, puerto 6543, verifica las limitaciones vigentes de prepared statements y configura `PDO::ATTR_EMULATE_PREPARES` mediante `DB_EMULATE_PREPARES`. Explica en el README cuándo usar el pooler si el hosting carece de IPv6 para la conexión directa.
- Laravel gestiona la autenticación. No uses Supabase Auth ni el cliente JavaScript de Supabase. Ninguna clave `service_role` ni credencial de base de datos debe llegar al navegador.
- Crea una migración y un comando idempotente `db:enable-rls` para activar RLS en todas las tablas existentes de `public` del proyecto dedicado a la tienda. Déjalas sin políticas de acceso público. Verifica que Laravel opera con el rol propietario esperado. Incluye una prueba que falle si alguna tabla de `public` queda sin RLS después de las migraciones.
- Almacena precios y totales como pesos COP enteros. En PostgreSQL utiliza un tipo entero de capacidad suficiente y restricciones `CHECK` para impedir valores negativos en precios y stock. Usa `jsonb` para JSON, `ILIKE` para búsqueda y `lockForUpdate()` en operaciones concurrentes de inventario.
- Imágenes en disco `supabase`, compatible con S3, mediante el adaptador Flysystem correspondiente. Bucket con lectura pública de imágenes de catálogo y escritura solo desde el servidor. En desarrollo local usa el disco `public`. Configura las credenciales con `SUPABASE_STORAGE_*`.

## Keep-alive y respaldo

- Tabla `keepalive_pings` con `id`, `source` de hasta 20 caracteres y `pinged_at` con zona horaria e índice.
- Comando `supabase:keepalive` que inserta un ping, borra registros de más de 14 días y cuenta los productos activos. Devuelve `SUCCESS` o `FAILURE` y registra errores sin exponer credenciales.
- Programa el comando cada 6 horas en `routes/console.php`, con `withoutOverlapping()` y `onOneServer()`.
- Ruta `GET /health/db`, limitada a 10 solicitudes por minuto. Compara `X-Health-Token` con `config('zaza.health_token')` mediante `hash_equals`. Si el secreto configurado está vacío o el token no coincide, responde 404. Con token válido inserta un registro `source=http` y devuelve JSON.
- Workflow `.github/workflows/keepalive.yml` cada 8 horas que consulte la ruta con `curl` y falle si la solicitud falla. Usa los secrets `HEALTH_TOKEN` y `APP_URL`.
- Documenta el cron `* * * * * php artisan schedule:run`, las limitaciones del plan Free y un script programable de respaldo con `pg_dump`. No prometas que los pings garantizan disponibilidad en producción.

# MODELOS Y RELACIONES

- `User`: `nombre_completo`, email único, contraseña con hash, teléfono, género nullable, dirección, avatar nullable, `acepta_marketing` e `is_admin`. Permite completar la dirección después del registro y exígela para el envío.
- `Category`: ROPA, ZAPATOS y ACCESORIOS. `Subcategory` pertenece a una categoría. Incluye camisas, hoodies, pantalones, bermudas, gorras y carrieles.
- `Product`: nombre, slug único, descripción, precio COP, categoría, subcategoría nullable, `activo`, `destacado`, `needs_price_review` y timestamps.
- `ProductImage`: producto, ruta y orden. `ProductColor`: producto, nombre e imagen. `ProductVariant`: producto, color si aplica, talla y stock. El stock corresponde a la variante que el cliente compra. Impide duplicados de la misma combinación, incluso para productos sin color.
- Tallas de ropa XS, S, M, L y XL. Tallas numéricas para zapatos y «Única» para accesorios.
- `Wishlist`: `user_id` y `product_id` con unicidad compuesta.
- `Cart` y `CartItem`: invitados en sesión y usuarios en BD. Al iniciar sesión, fusiona por variante, valida cantidades y comunica cualquier ajuste por disponibilidad.
- `Order`: usuario, referencia interna única, estado, `medio_pago`, `forma_pago`, fecha de pago, entrega estimada, total, datos de envío y `stock_reserved_until`.
- Estados de pedido: `PENDIENTE`, `PAGADO`, `EN_PREPARACION`, `ENVIADO`, `ENTREGADO`, `CANCELADO`, `EXPIRADO`.
- `OrderItem`: conserva un snapshot de nombre, color si aplica, talla, cantidad y precio unitario. Los pedidos históricos deben seguir siendo legibles aunque se edite o retire el producto.
- `Payment`: pedido, proveedor, identificador de pago del proveedor, estado, importe, moneda, `raw_payload` en `jsonb` y timestamps. Haz único el identificador del pago para ese proveedor. Distingue el identificador de preferencia de Checkout Pro del identificador de cada pago. Un pedido puede tener varios intentos. Conserva únicamente los datos necesarios para conciliación, sin datos de tarjeta ni secretos.
- `Banner`: imagen, texto, enlace, activo y orden.

# CRUD ADMINISTRATIVO DE PRODUCTOS

Implementa un `ProductResource` de Filament con las cuatro operaciones accesibles desde `/admin`. El administrador debe poder publicar un producto nuevo sin modificar código ni ejecutar seeders.

1. CREAR. Formulario para nombre, slug autogenerado y editable, descripción, precio COP, categoría, subcategoría, activo, destacado y revisión de precio. Desde el mismo recurso se deben poder cargar y ordenar imágenes, definir colores, crear tallas y asignar stock por variante.
2. LEER. Tabla administrativa con miniatura, nombre, categoría, precio, stock disponible y estado. Incluye búsqueda por nombre o slug, filtros por categoría, activo y destacado, y vista de detalle con imágenes y variantes. El administrador puede consultar también los productos inactivos.
3. ACTUALIZAR. Permite editar datos, precio, fotos, orden de galería, colores, tallas, stock y visibilidad. Valida que la subcategoría pertenezca a la categoría y que las tallas sean válidas. Un cambio de stock debe respetar las reservas existentes. Editar un producto no debe alterar un pedido previo.
4. ELIMINAR. Solicita confirmación antes de eliminar. Usa eliminación lógica para retirar el producto del catálogo sin romper pedidos ni reservas. Impide nuevas compras desde enlaces directos o carritos antiguos. Mantén disponibles los datos necesarios para resolver compras en curso.

Valida en servidor slug único, precio y stock enteros no negativos, combinaciones de variante únicas y archivos de imagen por tipo y tamaño. Aplica los cambios relacionados de forma consistente cuando falle una operación. Incluye un recurso Filament para administrar el banner del catálogo.

# PAGOS CON MERCADO PAGO CHECKOUT PRO COLOMBIA

Implementa `PaymentGateway` con `createCheckout`, `handleWebhook` y `getTransactionStatus`, y una implementación real `MercadoPagoGateway`. Verifica en la documentación oficial vigente los campos de preferencias, unidades monetarias, credenciales de prueba, URLs de retorno, eventos, validación de firma y consulta del pago. Documenta las fuentes utilizadas en el README.

## Inicio del pago

1. `POST /checkout` exige autenticación. Recalcula los precios, cantidades, total y stock en el servidor.
2. En `DB::transaction`, crea el pedido `PENDIENTE`, genera una referencia única y reserva las variantes durante 30 minutos mediante bloqueo de filas. Evita que un doble clic cree dos pedidos o dos reservas para el mismo checkout.
3. Una vez confirmada la transacción local, crea la preferencia de Checkout Pro desde el servidor. Asocia el pedido mediante `external_reference`, configura la moneda COP, los artículos, las URLs de retorno y la URL HTTPS de notificaciones según la documentación. Envía los precios en la unidad que exige Checkout Pro para COP. Por ejemplo, una camisa de 190.000 COP debe aparecer en el checkout por 190.000 COP.
4. Guarda el identificador de la preferencia y redirige al checkout alojado por Mercado Pago. El botón debe decir «Pagar con Mercado Pago». Si la creación de la preferencia falla o la respuesta se pierde, permite una recuperación consistente sin descontar el inventario de nuevo.
5. Configura la vigencia de la preferencia de acuerdo con la reserva cuando el proveedor lo permita. Considera expresamente los medios de pago cuya confirmación puede llegar después de los 30 minutos.

## Notificaciones y conciliación

- Expón `POST /webhooks/mercadopago`. Excluye únicamente esta ruta de CSRF. Valida la firma de Webhooks con el secreto y el procedimiento oficial de Mercado Pago para el evento configurado. Rechaza firmas inválidas y registra el incidente sin secretos.
- Para una notificación de pago válida, consulta el pago a Mercado Pago desde el servidor con el Access Token del entorno correspondiente. Antes de acreditar, verifica referencia, importe, moneda y cuenta receptora cuando corresponda. Si hay discrepancias, registra el caso y evita confirmar automáticamente el pedido.
- Procesa de forma idempotente con restricciones únicas y bloqueo del pedido. El mismo pago puede notificarse varias veces y cambiar de estado. Una actualización legítima debe procesarse aunque ya exista la fila `Payment`.
- Un pago aprobado y verificado marca `PAGADO` una sola vez y encola un único correo de confirmación después del commit. Un pago pendiente o en proceso mantiene el intento pendiente. Un intento rechazado o cancelado se registra sin cancelar un pedido ya pagado ni invalidar otro intento vigente.
- Implementa `orders:release-expired` cada 5 minutos. Para pedidos pendientes vencidos, concilia los pagos conocidos antes de liberar. Si corresponde, marca `EXPIRADO` y libera la reserva una sola vez. Coordina scheduler y webhook para que no puedan consumir y liberar simultáneamente la misma reserva.
- Si llega una aprobación después de liberar la reserva, registra el pago recibido y deja el caso identificado para conciliación. No confirmes el despacho ni descuentes stock ya vendido. Documenta cómo reconocer y resolver ese caso.
- `/pedidos/{order}/resultado` usa el estado verificado de la base de datos. Si la notificación aún no llegó, muestra «Estamos verificando tu pago». Los parámetros de retorno del navegador nunca autorizan marcar un pedido como pagado.
- La tienda no recibe ni almacena datos de tarjeta. Mantén el Access Token y el secreto del webhook exclusivamente en el servidor.
- Variables de la aplicación: `MERCADOPAGO_ENV`, `MERCADOPAGO_ACCESS_TOKEN` y `MERCADOPAGO_WEBHOOK_SECRET`. Define valores de entorno admitidos y usa pruebas por defecto. Añade otras variables únicamente si el flujo oficial elegido las requiere.

# DISEÑO Y COMPONENTES

- Estética luxury streetwear en negro. Fondo `#000000` con puntos blancos pequeños y difusos mediante CSS o SVG repetido.
- Superficies `#111111`, texto `#FFFFFF`, acento rojo `#E10600`, footer `#404040`, páginas claras `#C8C9C8`, inputs `#D9D9D9`.
- Botones principales negros con texto claro y forma de píldora. «Comprar ahora» blanco con texto negro.
- Títulos y lema con fuente condensada, negrita y cursiva, Anton o Bebas Neue, con espaciado amplio. Cuerpo en Quicksand.
- Tarjetas de producto con radio aproximado de 24 px. Carrito lateral blanco con esquinas izquierdas redondeadas.
- Bootstrap Icons para lupa, carrito con indicador rojo cuando contenga artículos, corazón y avatar circular.
- Logo con rayo rojo en `public/img/logo.png`, con fallback textual «ZAZA STORE».
- Mobile-first. Navbar colapsable, grilla de dos columnas en móvil y tres en escritorio. Carrito a pantalla completa en móvil.
- Accesibilidad AA: contraste, alt, etiquetas, foco visible, navegación por teclado y atributos ARIA en el panel del carrito.
- Componentes Blade: `<x-product-card>`, `<x-navbar>`, `<x-footer>`, `<x-cart-drawer>`, `<x-auth-card>` y `<x-pagination>`.

# NAVEGACIÓN Y PÁGINAS

Navbar del escaparate: logo enlazado al inicio, INICIO, ROPA, ZAPATOS y ACCESORIOS. Subraya la sección activa. A la derecha incluye búsqueda en vivo vía JSON, carrito lateral, favoritos y avatar. El avatar abre Mi cuenta si hay sesión o Iniciar sesión si es invitado.

1. Inicio `/`. Franja con «EL MUNDO ES TUYO, VISTELO A TU ESTILO». Hero con «ZAZA STORE», botón «Comprar ahora» hacia `/ropa`, imagen redondeada y texto «Bienvenido a ZAZA STORE. Aquí encuentras lo que te gusta, con estilo y buena vibra. ¡Explora, disfruta y hazlo tuyo!». Sección clara con logo circular, «Más que una marca, es un sueño hecho realidad.» y foto de modelos sobre panel gris. Sección «PRODUCTOS MÁS VENDIDOS» con «Descubre los productos favoritos por nuestros clientes» y cuatro productos activos destacados.
2. Catálogo `/ropa`, `/zapatos`, `/accesorios`. Banner administrable, botón «FILTRAR +», filtros por subcategoría, rango de precio y talla, y selector de orden. Conserva los filtros al paginar con `withQueryString()`. Muestra 12 productos por página, imagen, nombre, precio COP, favorito y acción de carrito. Solicita la variante cuando corresponda.
3. Detalle `/producto/{slug}`. Fondo `#C8C9C8`, galería, nombre, precio, favorito, miniaturas de color, talla seleccionada subrayada, tallas agotadas deshabilitadas, cantidad limitada al stock, «AÑADIR AL CARRITO» y descripción.
4. Carrito lateral «TU CARRITO». Por línea muestra imagen, nombre, talla, cantidad, precio unitario, subtotal, «EDITAR» y papelera. Pie con «Total artículos» como suma de unidades, «Total a pagar» con IVA incluido y «FINALIZAR COMPRA». Actualiza con fetch y Alpine. El servidor calcula importes.
5. Checkout `/checkout`. Exige sesión, precarga datos de envío y muestra el resumen y «Pagar con Mercado Pago». Resultado en `/pedidos/{order}/resultado` y seguimiento en `/pedidos/{order}`, con línea de estados, medio de pago, fecha de pago, entrega estimada, productos y total pagado. Aplica `OrderPolicy` a ambas rutas. Envía la confirmación mediante Mailable en cola al acreditar el pago.
6. Autenticación. Fondo negro con marca de agua «ZAZA STORE» diagonal, tarjeta `#111` e inputs píldora `#D9D9D9`. `/registro` solicita nombre y apellidos, correo, contraseña de mínimo ocho caracteres con control de visibilidad, teléfono, consentimiento de marketing y enlace a privacidad. Incluye «¿Ya tienes una cuenta?». `/iniciar-sesion` incluye recordar sesión, reCAPTCHA configurable y enlace de recuperación. `/olvide-mi-contrasena` responde «Comprueba tu bandeja de entrada» sin revelar si existe el correo. `/restablecer-contrasena/{token}` usa expiración de 720 minutos. Limita intentos de login y recuperación.
7. Mi cuenta `/cuenta`. Avatar, nombre, correo, género, dirección, teléfono, edición de datos, cambio de contraseña, «CONFIGURAR CUENTA», mis pedidos y mis favoritos. Nunca muestres la contraseña ni su hash. Muestra el enlace administrativo solo a administradores.
8. Quiénes somos `/quienes-somos`. Título «UBICACIÓN», mapa embebido configurable, «Estamos ubicados en la Cra 7 # 16-25, centro, Ibagué», «Nuestra historia: En ZAZA STORE todo comenzó con una idea simple: crear una marca de ropa masculina.» y tarjeta gris «¿Por qué elegirnos? En ZAZA STORE ofrecemos ropa de calidad, moderna y accesible, pensada para hombre.» con foto de modelo.
9. Páginas legales con contenido provisional editable: `/politica-de-devolucion-cambios-y-garantias`, `/politica-de-privacidad` con referencia a Ley 1581 de 2012 y habeas data, y `/cookies` con banner de consentimiento.
10. Favoritos en `/favoritos` y búsqueda en `/buscar?q=`.
11. Administración en `/admin` con acceso al CRUD de productos y gestión de banners.

# FOOTER

Fondo `#404040`. Centraliza los datos en `config/zaza.php`.

- INFORMACIÓN DE INTERÉS: Quiénes somos, política de devolución, cambios y garantías, política de privacidad y cookies.
- INFORMACIÓN DE CONTACTO: WhatsApp 322 8317011, enlace `https://wa.me/573228317011`, correo `storezaza848@gmail.com`, Instagram `@zazastore_ibg`, enlace `https://www.instagram.com/zazastore_ibg/` y dirección Cra 7 # 16-25, centro, Ibagué.

# DATOS INICIALES

Entrega `CategorySeeder`, `ProductSeeder` y un seeder para el administrador inicial. Usa imágenes placeholder reemplazables, cinco unidades por talla de ejemplo y cuatro productos destacados. Los precios siguientes están expresados en pesos COP.

ROPA

- Camisa blanca Emotions 190.000
- Camisa negra Emotions 170.000
- Hoodie Coach 270.000
- Hoodie Louis Vuitton 210.000
- Hoodie Jordan 210.000
- Bermuda Overzy 120.000
- Camisa Good Speed 170.000
- Camisa boxy fit Zuwon 170.000
- Pantalón descosido Overzy 210.000
- Pantalón DSquared 180.000
- Pantalón Slim Fit 210.000

ZAPATOS

- Asics Gel 1130 270.000
- Nocta Hot Step 2 220.000
- Osiris D3 Black 270.000
- Nike Air Max Plus 200.000
- Puma La France 270.000
- Asics Gel-NYC 210.000
- Air Force 1 160.000
- Puma 180 220.000
- Bapesta 250.000
- Nike Cortez 210.000
- LV Skate 230.000

ACCESORIOS

- Carriel Amiri 70.000
- Gorra roja Amiri 80.000
- Gorra Clemont 70.000
- Gorra New Era 90.000
- Gorra Lone Wolf 270.000, `needs_price_review=true`
- Carriel Gucci 210.000, `needs_price_review=true`
- Carriel Clemont 60.000
- Carriel Hunter 110.000
- Carriel Coach 150.000
- Gorra Gucci 70.000
- Carriel Burberry 80.000
- Gorra Louis Vuitton 110.000
- Gorra cerrada Hellstar 90.000

Descripción de ejemplo de Camisa blanca Emotions: «Camisa blanca de la marca Emotions elaborada en tela premium 70% algodón 30% poliéster. Construcción antiarrugas con acabado solido.»

# SEGURIDAD, CALIDAD Y PRUEBAS DE ACEPTACIÓN

- CSRF en formularios y fetch, hash de contraseñas mediante Laravel, `$fillable` explícito, `APP_DEBUG=false` en producción, cookies de sesión Secure/HttpOnly/SameSite según entorno y cabeceras de seguridad.
- Usa Form Requests o la validación equivalente de Filament. Aplica Policies en servidor. Evita N+1 mediante `with()`/`load()` y activa `Model::preventLazyLoading()` en local.
- Índices adecuados en slug, categoría, activo, destacado, referencia de pedido e identificadores de pago.
- SEO con título y meta por página, slugs, `sitemap.xml`, `robots.txt` y Open Graph en producto. Imágenes con carga diferida donde corresponda. En producción, cachés de configuración, rutas y vistas y build minificado.
- Ningún secreto en el repositorio, frontend, logs ni fixtures.

Cubre como mínimo estos escenarios:

1. El registro público crea un usuario normal. Enviar `is_admin=true` durante registro o edición de perfil no concede privilegios.
2. Un invitado debe autenticarse para entrar al panel. Un usuario normal carece de acceso al panel y a cada operación administrativa, incluso mediante solicitudes directas. Un administrador sí tiene acceso.
3. Desde el recurso real de Filament, un administrador crea un producto con imagen y variantes, lo encuentra, consulta su detalle, cambia precio y stock y lo elimina con confirmación. Verifica los cambios en BD y en el catálogo público.
4. El formulario administrativo rechaza datos inválidos. Un producto inactivo o eliminado desaparece de catálogo, búsqueda y compra directa. El pedido histórico conserva su detalle.
5. El carrito se fusiona al iniciar sesión y dos compras concurrentes no pueden reservar la última unidad simultáneamente.
6. El checkout genera una preferencia en COP por el total correcto. Prueba notificación válida, firma inválida, importe o referencia incorrectos, notificaciones duplicadas, cambios de estado de un mismo pago y varios intentos para un pedido.
7. Verifica retorno del navegador antes del webhook, aprobación tardía, expiración, liberación única del stock y envío único del correo. Un cliente no puede consultar pedidos ajenos.
8. Verifica RLS en todas las tablas de `public` y acceso a `/health/db` con token válido, inválido y vacío.

# FASES DE ENTREGA

Fase 1. Instala el stack, configura Supabase, modelos, migraciones, RLS, keep-alive, seeders, roles y restricción de acceso al panel. Entrega layout y sistema de diseño base.

Fase 2. Implementa inicio, catálogo, filtros, paginación, detalle de producto y búsqueda en vivo.

Fase 3. Implementa carrito de invitado y autenticado, fusión al login, favoritos y panel lateral con Alpine.

Fase 4. Personaliza autenticación, recuperación de contraseña con expiración de 12 horas, perfil y navegación según rol.

Fase 5. Implementa checkout con Mercado Pago Checkout Pro, reservas, webhook, conciliación, liberación de vencidos, resultados, seguimiento, correos y pruebas de pagos. Documenta cómo hacer una compra de prueba y cómo verificar la notificación.

Fase 6. Completa el CRUD de productos en Filament, gestión de banners, Supabase Storage, páginas legales, ubicación, SEO y pruebas finales. Demuestra con un producto de ejemplo el recorrido crear → ver → editar → eliminar y su efecto en la tienda. Entrega README y despliegue.

# ENTREGABLES Y ARRANQUE

Entrega código funcional, `composer.json`, `package.json`, `.env.example`, migraciones, seeders, pruebas, workflow de keep-alive, script de `pg_dump` y `README.md`.

El README debe permitir a otra persona instalar el proyecto desde cero, configurar `.env`, crear el administrador inicial, entrar a `/admin`, gestionar un producto y probar una compra con Mercado Pago. Documenta el pooler, almacenamiento, webhook HTTPS, entorno de pruebas, worker, scheduler y despliegue con Nginx, PHP-FPM y Supervisor.

Verifica esta secuencia en un entorno nuevo con PHP, Composer, Node y PostgreSQL disponibles:

1. `composer install`
2. `npm install`
3. `cp .env.example .env`
4. Completar en `.env` la conexión a PostgreSQL y las credenciales iniciales del administrador. Para las pruebas con el proveedor, configurar las credenciales de prueba de Mercado Pago.
5. `php artisan key:generate`
6. `php artisan migrate --seed`
7. `php artisan storage:link`
8. `npm run build`
9. `php artisan test` usando una base de datos de pruebas aislada.
10. `php artisan serve`

Incluye los comandos para iniciar el worker y el scheduler en desarrollo. No des por completada una fase con código a medias, pruebas fallidas sin explicar o integraciones simuladas presentadas como reales.

Empieza ahora por la Fase 1 y detente al presentar sus resultados.
