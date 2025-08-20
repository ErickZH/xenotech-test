<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderDiscountService;
use App\Services\OrderStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderController extends Controller
{
    private OrderDiscountService $discountService;

    public function __construct(OrderDiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

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

            // Calcular el precio final con descuentos usando el patrón Decorator
            $baseAmount = array_reduce($validated['items'], function ($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0);

            $context = [
                'order_date' => now(),
                'user_id' => $validated['user_id'],
            ];

            $discountResult = $this->discountService->calculateFinalAmount($baseAmount, $context);

            // Crear la orden con la información de descuentos
            $order = Order::create([
                'user_id' => $validated['user_id'],
                'original_amount' => $discountResult['original_amount'],
                'discount_amount' => $discountResult['total_discounts'],
                'total_amount' => $discountResult['final_amount'],
                'discount_details' => $discountResult['discounts_applied'],
            ]);

            $order->items()->createMany($validated['items']);

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
    public function update(UpdateOrderRequest $request, Order $order): OrderResource|JsonResponse
    {
        return DB::transaction(function () use ($request, $order) {
            $validated = $request->validated();

            // Manejar cambio de estado con State Machine
            try {
                OrderStateMachine::transition($order, $validated['status']);
            } catch (InvalidArgumentException $e) {
                return response()->json([
                    'message' => 'Error en transición de estado',
                    'error' => $e->getMessage(),
                    'current_status' => $order->status,
                    'available_transitions' => OrderStateMachine::getAvailableTransitions($order->status),
                ], 422);
            }

            // Cargar las relaciones para la respuesta
            $order->load(['items', 'user']);

            return new OrderResource($order);
        });
    }
}
