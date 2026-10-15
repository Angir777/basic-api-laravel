<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Permission\PermissionQueryResource;
use App\Http\Resources\V1\Permission\PermissionResource;
use App\Services\User\PermissionService;
use App\Utils\Response\ResponseUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    public function __construct(protected PermissionService $permissionService) {}

    protected function allowedSorts(): array
    {
        return [
            AllowedSort::field('id', 'id'),
            AllowedSort::field('name', 'name'),
            AllowedSort::field('guardName', 'guard_name'),
            AllowedSort::field('groupName', 'group_name'),
        ];
    }

    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('id', 'id'),
            AllowedFilter::partial('name', 'name'),
            AllowedFilter::exact('guardName', 'guard_name'),
            AllowedFilter::exact('groupName', 'group_name'),
        ];
    }

    public function getAll(Request $request): JsonResponse
    {
        $data = QueryBuilder::for(Permission::class)
            ->defaultSort('name')
            ->allowedSorts($this->allowedSorts())
            ->allowedFilters($this->allowedFilters())
            ->get();

        return ResponseUtils::response(data: PermissionResource::collection($data));
    }

    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->pageSize ?? config('query-builder.default_page_size');

        $data = QueryBuilder::for(Permission::class)
            ->defaultSort('name')
            ->allowedSorts($this->allowedSorts())
            ->allowedFilters($this->allowedFilters())
            ->paginate($pageSize);

        return ResponseUtils::response(data: new PermissionQueryResource($data));
    }

    public function getById(Request $request, int $permissionId): JsonResponse
    {
        $data = Permission::findOrFail($permissionId);

        return ResponseUtils::response(data: new PermissionResource($data));
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'groupName' => ['required', 'string', 'max:255'],
        ]);

        try {
            $data = $this->permissionService->create(data: $validated);

            return ResponseUtils::response(data: new PermissionResource($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_CREATE_PERMISSION', status: Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(Request $request, int $permissionId): JsonResponse
    {
        $permission = Permission::findOrFail($permissionId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'groupName' => ['required', 'string', 'max:255'],
        ]);

        try {
            $data = $this->permissionService->update(permission: $permission, data: $validated);

            return ResponseUtils::response(data: new PermissionResource($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_UPDATE_PERMISSION', status: Response::HTTP_BAD_REQUEST);
        }
    }

    public function delete(Request $request, int $permissionId): JsonResponse
    {
        $permission = Permission::findOrFail($permissionId);

        try {
            $data = $this->permissionService->delete(permission: $permission);

            return ResponseUtils::response(data: new PermissionResource($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_DELETE_PERMISSION', status: Response::HTTP_BAD_REQUEST);
        }
    }
}
