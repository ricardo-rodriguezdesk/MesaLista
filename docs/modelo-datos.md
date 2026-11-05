# Modelo de datos — MesaLista

Base de datos PostgreSQL alojada en Supabase. Las tablas se crean con migraciones de Laravel.

## Diagrama entidad-relación

```mermaid
erDiagram
    roles ||--o{ usuarios : "asigna"
    usuarios ||--o| empleados : "es"
    empleados ||--o{ asistencias : "registra"
    categorias ||--o{ productos : "agrupa"
    zonas ||--o{ mesas : "contiene"
    mesas ||--o{ cuentas : "atiende"
    usuarios ||--o{ cuentas : "abre (mesero)"
    cuentas ||--|{ cuenta_detalle : "incluye"
    productos ||--o{ cuenta_detalle : "se pide en"
    cuentas ||--o{ tickets : "genera"
    tickets ||--|{ pagos : "se paga con"
    usuarios ||--o{ pagos : "cobra (cajero)"
    tickets ||--o| facturas : "se factura en"
    usuarios ||--o{ bitacora : "realiza"

    roles {
        bigint id PK
        string nombre UK "administrador, cajero, mesero, cocina"
        string descripcion
    }

    usuarios {
        bigint id PK
        bigint rol_id FK
        string nombre
        string usuario UK "acceso con PIN"
        string pin "hash bcrypt"
        string email UK "acceso con Google"
        string google_id UK
        boolean activo
        timestamp created_at
        timestamp updated_at
    }

    empleados {
        bigint id PK
        bigint usuario_id FK "UK"
        string telefono
        string puesto
        date fecha_ingreso
        timestamp created_at
        timestamp updated_at
    }

    asistencias {
        bigint id PK
        bigint empleado_id FK
        timestamp entrada
        timestamp salida "nulo mientras trabaja"
        decimal horas "calculado al registrar salida"
    }

    categorias {
        bigint id PK
        string nombre UK
        int orden
        boolean activo
    }

    productos {
        bigint id PK
        bigint categoria_id FK
        string nombre
        string descripcion
        decimal precio "precio final con IVA"
        decimal tasa_iva "0.16"
        string clave_prod_serv "catalogo SAT"
        string clave_unidad "catalogo SAT, ej. E48"
        boolean activo
        timestamp created_at
        timestamp updated_at
    }

    zonas {
        bigint id PK
        string nombre
        string tipo "salon, terraza, barra"
        int x
        int y
        int ancho
        int alto
    }

    mesas {
        bigint id PK
        bigint zona_id FK
        string nombre
        int capacidad
        string forma "cuadrada, redonda, barra"
        int x
        int y
        int ancho
        int alto
        int rotacion
        string estado "libre, ocupada, cuenta_pedida"
        boolean activo "quitar del croquis sin borrar historial"
    }

    cuentas {
        bigint id PK
        bigint mesa_id FK
        bigint mesero_id FK
        int comensales
        string estado "abierta, cuenta_pedida, cerrada, cancelada"
        decimal subtotal
        decimal iva
        decimal total
        timestamp abierta_en
        timestamp cerrada_en
    }

    cuenta_detalle {
        bigint id PK
        bigint cuenta_id FK
        bigint producto_id FK
        int cantidad
        decimal precio_unitario "copia del precio al pedir"
        decimal tasa_iva
        string notas "ej. sin cebolla"
        int comensal "para dividir la cuenta"
        string estado "pendiente, preparando, listo, entregado, cancelado"
        timestamp enviado_en
        timestamp listo_en
    }

    tickets {
        bigint id PK
        bigint cuenta_id FK
        string folio UK
        decimal subtotal
        decimal iva
        decimal propina
        decimal total
        boolean facturado
        timestamp created_at
    }

    pagos {
        bigint id PK
        bigint ticket_id FK
        bigint cajero_id FK
        string metodo "efectivo, tarjeta, transferencia"
        decimal monto
        decimal recibido
        decimal cambio
        timestamp created_at
    }

    facturas {
        bigint id PK
        bigint ticket_id FK "UK"
        string facturapi_id
        string uuid "folio fiscal"
        string rfc
        string razon_social
        string regimen_fiscal
        string codigo_postal
        string uso_cfdi
        string email
        string xml_path "Supabase Storage"
        string pdf_path "Supabase Storage"
        string estado "pendiente, timbrada, cancelada, error"
        text error "respuesta del PAC si falla"
        timestamp created_at
    }

    bitacora {
        bigint id PK
        bigint usuario_id FK
        string accion "cancelar_platillo, cambiar_precio"
        string entidad
        bigint entidad_id
        json valores_anteriores
        json valores_nuevos
        timestamp created_at
    }
```

## Decisiones de diseño

- **usuarios y empleados separados.** `usuarios` guarda cómo se entra al sistema (PIN o Google) y el rol; `empleados` guarda los datos de la persona. El administrador puede entrar con Google sin ser empleado de piso.
- **Precio con IVA incluido.** En México el precio del menú ya incluye IVA, así que se guarda el precio final y la tasa. El precio antes de IVA se calcula: `precio / (1 + tasa_iva)`, y el IVA es la diferencia. Así el precio que ve el cliente nunca cambia por redondeos.
- **Copia del precio en cada platillo pedido.** `cuenta_detalle.precio_unitario` guarda el precio del momento; si el administrador cambia el precio después, las cuentas anteriores no se alteran.
- **Una cuenta puede generar varios tickets.** Al dividir la cuenta, cada parte genera su propio ticket con folio único, y cada ticket se puede pagar con uno o más métodos (`pagos`). Cada ticket se factura por separado.
- **Croquis con coordenadas.** Zonas y mesas guardan `x`, `y`, `ancho`, `alto` (y `rotacion` en mesas) para dibujar el plano y moverlas arrastrando.
- **Estado de mesas y platillos en columnas propias.** Supabase Realtime escucha los cambios en `mesas.estado` y `cuenta_detalle.estado` para actualizar todas las pantallas al instante.
- **Datos fiscales del SAT en productos.** `clave_prod_serv` y `clave_unidad` son obligatorios para timbrar el CFDI 4.0.
- **Mesas que se desactivan.** `mesas.activo` permite quitar una mesa del croquis sin borrar las cuentas que tuvo; por la misma razón, no se puede eliminar una mesa, cuenta o producto con historial.
- **Estados controlados por la base de datos.** Los campos `estado`, `tipo`, `forma` y `metodo` son `enum`: PostgreSQL rechaza cualquier valor fuera de la lista.
- **Facturas con seguimiento de errores.** Una factura nace como `pendiente`; si el PAC rechaza el timbrado se guarda su respuesta en `error` para poder corregir los datos y reintentar.
- **Bitácora con JSON.** Guarda los valores antes y después del cambio para saber quién canceló un platillo o modificó un precio, y cuándo.

Laravel crea además sus tablas internas (`personal_access_tokens` de Sanctum, `migrations`, `cache`, `jobs`).
