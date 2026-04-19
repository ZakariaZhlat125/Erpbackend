<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends BaseApiController
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->paymentService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->create($request->validated());

        return $this->createdResponse(
            new PaymentResource($payment)
        );
    }

    public function show(int $id): JsonResponse
    {
        $payment = $this->paymentService->findById($id);

        if (!$payment) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new PaymentResource($payment)
        );
    }

    public function update(UpdatePaymentRequest $request, int $id): JsonResponse
    {
        if (!$this->paymentService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->paymentService->update($id, $request->validated());
        $payment = $this->paymentService->findById($id);

        return $this->successResponse(
            new PaymentResource($payment),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->paymentService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->paymentService->delete($id);

        return $this->noContentResponse();
    }
}
