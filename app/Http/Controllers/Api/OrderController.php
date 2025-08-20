<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->get('per_page', 15);
        $perPage = min($perPage, 30); // Limitar máximo 30 resultados por página

        $query = Order::with(['items', 'user']);

        // Filtros opcionales
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $orders = $query->paginate($perPage);

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request): OrderResource
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            // Crear la orden
            $order = Order::create([
                'user_id' => $validated['user_id'],
                'total_amount' => $validated['total_amount'],
                'status' => $validated['status'] ?? 'pending',
            ]);

            // Crear los items si existen
            if (isset($validated['items']) && !empty($validated['items'])) {
                $order->items()->createMany($validated['items']);
            }

            // Cargar las relaciones para la respuesta
            $order->load(['items', 'user']);

            return new OrderResource($order);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order): OrderResource
    {
        $order->load(['items', 'user']);
        return new OrderResource($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        return DB::transaction(function () use ($request, $order) {
            $validated = $request->validated();

            // Actualizar la orden
            $order->update(array_filter([
                'user_id' => $validated['user_id'] ?? null,
                'total_amount' => $validated['total_amount'] ?? null,
                'status' => $validated['status'] ?? null,
            ]));

            // Actualizar items si se proporcionan
            if (isset($validated['items'])) {
                // Eliminar items existentes y crear nuevos (simplificado)
                // En una implementación más sofisticada, se podría manejar updates/deletes individuales
                $order->items()->delete();
                $order->items()->createMany($validated['items']);
            }

            // Cargar las relaciones para la respuesta
            $order->load(['items', 'user']);

            return new OrderResource($order);
        });
    }
}
