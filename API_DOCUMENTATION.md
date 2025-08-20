# API Documentation - Orders CRUD

## Base URL
`/api`

## Endpoints

### 1. List Orders
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

### 2. Create Order
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

### 3. Show Order
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

### 4. Update Order Status
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

## Validation Rules

### Store Order (POST):
- `user_id`: Requerido
- `items`: Requerido
- `items.*.product_name`: Requerido
- `items.*.quantity`: Requerido
- `items.*.price`: Requerido

### Update Order (PUT/PATCH):
- `status`: Requerido: pending, processing, shipped, delivered, cancelled

## Order State Machine

### Valid Status Transitions:
- `pending` → `processing`, `cancelled`
- `processing` → `shipped`, `cancelled`
- `shipped` → `delivered`, `cancelled`
- `delivered` → `cancelled`
- `cancelled` → (ninguno)

### Automatic Features:
- **Discount Calculation**: Automatically applies available discounts using Decorator pattern
- **Notifications**: Sends notifications based on user type when status changes
- **Database Transactions**: Ensures data consistency for all operations

## Discount System

### Available Discounts:
- **Monday Discount**: Applied on Mondays
- **Random Discount**: Applied randomly

### Discount Response Structure:
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

## Notification System

Notifications are sent automatically when order status changes:

- **Regular users**: No notifications
- **Premium users**: Email notifications via webhook
- **VIP users**: WhatsApp notifications via webhook

## Error Responses

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

## Status Codes
- `200`: Success
- `201`: Created
- `404`: Not Found
- `422`: Validation Error / Invalid State Transition
- `500`: Server Error

## Testing Examples

### Using cURL:

#### Create Order:
```bash
curl -X POST http://xenotech-test.test/api/orders \
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

#### List Orders with Filters:
```bash
curl -X GET "http://xenotech-test.test/api/orders?per_page=5&status=pending&user_id=1&sort_by=created_at&sort_direction=desc" \
  -H "Accept: application/json"
```

#### Get Single Order:
```bash
curl -X GET http://xenotech-test.test/api/orders/1 \
  -H "Accept: application/json"
```

#### Update Order Status:
```bash
curl -X PUT http://xenotech-test.test/api/orders/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "processing"
  }'
```

## Features Summary

- ✅ **CRUD Operations**: Complete Create, Read, Update operations
- ✅ **Automatic Discounts**: Decorator pattern implementation
- ✅ **State Machine**: Controlled status transitions
- ✅ **Notifications**: Strategy pattern for different user types
- ✅ **Database Transactions**: Atomic operations
- ✅ **Pagination & Filtering**: Flexible querying
- ✅ **Resource Relationships**: Auto-loaded items and user data
- ✅ **Comprehensive Validation**: Request validation with custom messages
