# API Documentation - Orders CRUD

## Base URL
`http://localhost:8080/api`

## Endpoints

### 1. Listar Ordenes
**GET** `/api/orders`

Lista todas las órdenes con paginación y filtros opcionales. Incluye automáticamente las relaciones con items y usuario.

#### Query Parameters:
- `per_page` (integer, max: 30): Número de resultados por página (default: 10)
- `status` (string): Filtrar por estado (pending, processing, shipped, delivered, cancelled)
- `user_id` (integer): Filtrar por ID de usuario
- `sort_by` (string): Campo para ordenamiento (default: created_at)
- `sort_direction` (string): Dirección del ordenamiento (asc, desc, default: desc)

#### Response (200):
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "original_amount": "100.00",
      "discount_amount": "15.00",
      "total_amount": "85.00",
      "status": "pending",
      "discount_details": [
        {
          "type": "monday_discount",
          "amount": 10.00,
          "description": "Monday discount 10%"
        },
        {
          "type": "random_discount",
          "amount": 5.00,
          "description": "Random discount 5%"
        }
      ],
      "items_count": 2,
      "items": [
        {
          "id": 1,
          "product_name": "Laptop",
          "quantity": 1,
          "price": "75.00",
          "subtotal": 75.00
        },
        {
          "id": 2,
          "product_name": "Mouse",
          "quantity": 1,
          "price": "25.00",
          "subtotal": 25.00
        }
      ],
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "created_at": "2025-08-19T23:00:00.000000Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### 2. Crear Orden
**POST** `/api/orders`

Crea una nueva orden con descuentos automáticos aplicados.

#### Request Body:
```json
{
  "user_id": 1,
  "items": [
    {
      "product_name": "Laptop",
      "quantity": 1,
      "price": 999.99
    },
    {
      "product_name": "Mouse",
      "quantity": 2,
      "price": 25.00
    }
  ]
}
```

#### Response (201):
```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "original_amount": "1049.99",
    "discount_amount": "52.50",
    "total_amount": "997.49",
    "status": "pending",
    "discount_details": [
      {
        "type": "monday_discount",
        "amount": 52.50,
        "description": "Monday discount 5%"
      }
    ],
    "items_count": 2,
    "items": [
      {
        "id": 1,
        "product_name": "Laptop",
        "quantity": 1,
        "price": "999.99",
        "subtotal": 999.99
      },
      {
        "id": 2,
        "product_name": "Mouse",
        "quantity": 2,
        "price": "25.00",
        "subtotal": 50.00
      }
    ],
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "created_at": "2025-08-19T23:00:00.000000Z"
  }
}
```

### 3. Ver Orden
**GET** `/api/orders/{id}`

Obtiene una orden específica con sus items y usuario relacionado.

#### Response (200):
```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "original_amount": "100.00",
    "discount_amount": "0.00",
    "total_amount": "100.00",
    "status": "pending",
    "discount_details": [],
    "items_count": 1,
    "items": [
      {
        "id": 1,
        "product_name": "Test Product",
        "quantity": 1,
        "price": "100.00",
        "subtotal": 100.00
      }
    ],
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "created_at": "2025-08-19T23:00:00.000000Z"
  }
}
```

### 4. Actualizar Status de Orden
**PUT/PATCH** `/api/orders/{id}`

Actualiza el estado de una orden usando State Machine con validación de transiciones. Envía notificaciones automáticamente.

#### Request Body:
```json
{
  "status": "processing"
}
```

#### Response Success (200):
```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "original_amount": "100.00",
    "discount_amount": "0.00",
    "total_amount": "100.00",
    "status": "processing",
    "discount_details": [],
    "items_count": 1,
    "items": [...],
    "user": {...},
    "created_at": "2025-08-19T23:00:00.000000Z"
  }
}
```

#### Response Error - Invalid Transition (422):
```json
{
  "message": "Error en transición de estado",
  "error": "No se puede cambiar el estado de 'delivered' a 'processing'. Estados disponibles: cancelled",
  "current_status": "delivered",
  "available_transitions": ["cancelled"]
}
```

## Reglas de validación

### Crear Orden (POST):
- `user_id`: Requerido
- `items`: Requerido
- `items.*.product_name`: Requerido
- `items.*.quantity`: Requerido
- `items.*.price`: Requerido

### Actualizar status de orden (PUT/PATCH):
- `status`: Requerido: pending, processing, shipped, delivered, cancelled

### Transiciones validas de cambio de estado:
- `pending` → `processing`, `cancelled`
- `processing` → `shipped`, `cancelled`
- `shipped` → `delivered`, `cancelled`
- `delivered` → `cancelled`
- `cancelled` → (ninguno)

### Estructura de respuesta de descuentos:
```json
{
  "original_amount": "100.00",
  "discount_amount": "15.00",
  "total_amount": "85.00",
  "discount_details": [
    {
      "type": "monday_discount",
      "amount": 10.00,
      "description": "Monday discount 10%"
    }
  ]
}
```

## Sistema de notificaciones

Las notificaciones se envían automáticamente cuando cambia el estado del pedido:

- **Usuario Regular**: Sin notifiación
- **Usuario Premium**: Notificación por email via webhook
- **Usuario VIP**: Notificación por WhatsApp via webhook

## Respuestas de error

### Validation Error (422):
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "user_id": ["The user id field is required."],
    "items": ["The items field is required."]
  }
}
```

### State Transition Error (422):
```json
{
  "message": "Error en transición de estado",
  "error": "No se puede cambiar el estado de 'delivered' a 'pending'. Estados disponibles: cancelled",
  "current_status": "delivered",
  "available_transitions": ["cancelled"]
}
```

### Not Found (404):
```json
{
  "message": "No query results for model [App\\Models\\Order] 123"
}
```

## HTTP Status Codes
- `200`: Success
- `201`: Created
- `404`: Not Found
- `422`: Validation Error / Invalid State Transition
- `500`: Server Error

## Ejemplos de prueba

### Using cURL:

#### Crear Order:
```bash
curl -X POST http://localhost:8080/api/orders \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "user_id": 1,
    "items": [
      {
        "product_name": "Laptop",
        "quantity": 1,
        "price": 999.99
      },
      {
        "product_name": "Mouse",
        "quantity": 1,
        "price": 25.00
      }
    ]
  }'
```

#### Listar ordenes con filtros:
```bash
curl -X GET "http://localhost:8080/api/orders?per_page=5&status=pending&user_id=1&sort_by=created_at&sort_direction=desc" \
  -H "Accept: application/json"
```

#### Obtener una orden:
```bash
curl -X GET http://localhost:8080/api/orders/1 \
  -H "Accept: application/json"
```

#### Actualizar status de una orden:
```bash
curl -X PUT http://localhost:8080/api/orders/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "processing"
  }'
```

## Resumen de características

- ✅ **Operaciones CRUD**: Operaciones completas de creación, lectura y actualización
- ✅ **Descuentos automáticos**: Implementación del patrón Decorator
- ✅ **Máquina de estados**: Transiciones de estado controladas
- ✅ **Notificaciones**: Patrón de estrategia para diferentes tipos de usuario
- ✅ **Transacciones de base de datos**: Operaciones atómicas
- ✅ **Paginación y filtrado**: Consultas flexibles
- ✅ **Relaciones de recursos**: Carga automática de elementos y datos de usuario
- ✅ **Validación completa**: Validación de solicitudes con mensajes personalizados
