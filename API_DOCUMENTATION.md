# API Documentation - Orders CRUD

## Endpoints

### 1. List Orders
**GET** `/api/orders`

Lista todas las órdenes con paginación y filtros opcionales.

#### Query Parameters:
- `per_page` (integer, max: 100): Número de resultados por página (default: 15)
- `status` (string): Filtrar por estado (pending, processing, shipped, delivered, cancelled)
- `user_id` (integer): Filtrar por ID de usuario
- `sort_by` (string): Campo para ordenamiento (default: created_at)
- `sort_direction` (string): Dirección del ordenamiento (asc, desc, default: desc)

#### Response:
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "total_amount": "150.50",
      "status": "pending",
      "items_count": 2,
      "items": [
        {
          "id": 1,
          "product_name": "Product 1",
          "quantity": 2,
          "price": "75.25",
          "subtotal": 150.50,
          "created_at": "2025-08-19T23:00:00.000000Z",
          "updated_at": "2025-08-19T23:00:00.000000Z"
        }
      ],
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "created_at": "2025-08-19T23:00:00.000000Z",
      "updated_at": "2025-08-19T23:00:00.000000Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### 2. Create Order
**POST** `/api/orders`

Crea una nueva orden con sus items.

#### Request Body:
```json
{
  "user_id": 1,
  "items": [ // opcional
    {
      "product_name": "Product 1",
      "quantity": 2,
      "price": 75.25
    }
  ]
}
```

#### Response:
```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "total_amount": "150.50",
    "status": "pending",
    "items": [...],
    "user": {...},
    "created_at": "2025-08-19T23:00:00.000000Z",
    "updated_at": "2025-08-19T23:00:00.000000Z"
  }
}
```

### 3. Show Order
**GET** `/api/orders/{id}`

Obtiene una orden específica con sus items y usuario relacionado.

#### Response:
```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "total_amount": "150.50",
    "status": "pending",
    "items": [...],
    "user": {...},
    "created_at": "2025-08-19T23:00:00.000000Z",
    "updated_at": "2025-08-19T23:00:00.000000Z"
  }
}
```

### 4. Update Order
**PUT/PATCH** `/api/orders/{id}`

Actualiza una orden existente.

#### Request Body:
```json
{
  "status": "processing"
}
```

### 5. Delete Order
**DELETE** `/api/orders/{id}`

Elimina una orden y todos sus items relacionados.

#### Response:
```json
{
  "message": "Order deleted successfully"
}
```

## Validation Rules

### Store Order:
- `user_id`: required, integer, must exist in users table
- `total_amount`: required, numeric, minimum 0
- `status`: optional, string, must be one of: pending, processing, shipped, delivered, cancelled
- `items`: optional, array, minimum 1 item if provided
- `items.*.product_name`: required if items provided, string, max 255 characters
- `items.*.quantity`: required if items provided, integer, minimum 1
- `items.*.price`: required if items provided, numeric, minimum 0

### Update Order:
- `user_id`: optional, integer, must exist in users table
- `total_amount`: optional, numeric, minimum 0
- `status`: optional, string, must be one of: pending, processing, shipped, delivered, cancelled
- `items`: optional, array, minimum 1 item if provided
- `items.*.product_name`: required if items provided, string, max 255 characters
- `items.*.quantity`: required if items provided, integer, minimum 1
- `items.*.price`: required if items provided, numeric, minimum 0

## Error Responses

### Validation Error (422):
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "user_id": ["The user id field is required."],
    "total_amount": ["The total amount must be at least 0."]
  }
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
- `422`: Validation Error
- `500`: Server Error

## Testing Examples

### Using cURL:

#### Create Order:
```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "user_id": 1,
    "total_amount": 150.50,
    "status": "pending",
    "items": [
      {
        "product_name": "Laptop",
        "quantity": 1,
        "price": 150.50
      }
    ]
  }'
```

#### Get All Orders:
```bash
curl -X GET "http://localhost:8000/api/orders?per_page=10&status=pending" \
  -H "Accept: application/json"
```

#### Get Single Order:
```bash
curl -X GET http://localhost:8000/api/orders/1 \
  -H "Accept: application/json"
```

#### Update Order:
```bash
curl -X PUT http://localhost:8000/api/orders/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "processing",
    "total_amount": 200.00
  }'
```

#### Delete Order:
```bash
curl -X DELETE http://localhost:8000/api/orders/1 \
  -H "Accept: application/json"
```
