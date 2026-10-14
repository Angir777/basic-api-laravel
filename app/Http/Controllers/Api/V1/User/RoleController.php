<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Permission\SimplePermissionResource;
use App\Http\Resources\V1\Role\RoleHistoryResource;
use App\Http\Resources\V1\Role\RoleQueryResource;
use App\Http\Resources\V1\Role\RoleResource;
use App\Models\RoleHistory;
use App\QueryFilters\DateFilter;
use App\Services\User\RoleService;
use App\Utils\Response\ResponseUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    /**
     * @param  protected
     */
    public function __construct(protected RoleService $roleService) {}

    protected function allowedSorts(): array
    {
        return [
            AllowedSort::field('id', 'id'),
            AllowedSort::field('name', 'name'),
            AllowedSort::field('guardName', 'guard_name'),
            AllowedSort::field('createdAt', 'created_at'),
            AllowedSort::field('updatedAt', 'updated_at'),
        ];
    }

    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('id', 'id'),
            AllowedFilter::partial('name', 'name'),
            AllowedFilter::exact('guardName', 'guard_name'),
            AllowedFilter::custom('createdAt', new DateFilter),
            AllowedFilter::custom('updatedAt', new DateFilter),
        ];
    }

    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->page_size ?? config('query-builder.default_page_size');

        $data = QueryBuilder::for(Role::class)
            ->defaultSort('name')
            ->allowedSorts($this->allowedSorts())
            ->allowedFilters($this->allowedFilters())
            ->paginate($pageSize);

        return ResponseUtils::response(data: new RoleQueryResource($data));
    }

    public function getAll(Request $request): JsonResponse
    {
        $data = QueryBuilder::for(Role::class)
            ->defaultSort('name')
            ->allowedSorts($this->allowedSorts())
            ->allowedFilters($this->allowedFilters())
            ->get();

        return ResponseUtils::response(data: RoleResource::collection($data));
    }

    /**
     * @param  mixed  $roleId
     */
    public function getById(Request $request, $roleId): JsonResponse
    {
        $data = Role::findOrFail($roleId);

        return ResponseUtils::response(data: new RoleResource($data));
    }

    public function getRoleHistory(Request $request, Role $role): JsonResponse
    {
        try {
            $data = RoleHistory::where('role_id', '=', $role->id)->orderBy('id', 'desc')->get();

            return ResponseUtils::response(data: RoleHistoryResource::collection($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), $exception->getTrace());
            throw new ApiException(message: 'CANT_GET_HISTORY', status: Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['required', 'array', 'min:1'],
        ]);

        try {
            $data = $this->roleService->create(data: $validated);

            return ResponseUtils::response(data: new RoleResource($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), $exception->getTrace());
            throw new ApiException(message: 'CANT_CREATE_ROLE', status: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param  mixed  $roleId
     */
    public function update(Request $request, $roleId): JsonResponse
    {
        $role = Role::findOrFail($roleId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['required', 'array', 'min:1'],
        ]);

        try {
            $data = $this->roleService->update(role: $role, data: $validated);

            return ResponseUtils::response(data: new RoleResource($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), $exception->getTrace());
            throw new ApiException(message: 'CANT_UPDATE_ROLE', status: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param  mixed  $roleId
     */
    public function delete(Request $request, $roleId): JsonResponse
    {
        $role = Role::findOrFail($roleId);

        try {
            $data = $this->roleService->delete(role: $role);

            return ResponseUtils::response(data: new RoleResource($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), $exception->getTrace());
            throw new ApiException(message: 'CANT_DELETE_ROLE', status: Response::HTTP_BAD_REQUEST);
        }
    }

    public function getPermissions(Request $request): JsonResponse
    {
        try {
            $data = Permission::all();

            return ResponseUtils::response(data: SimplePermissionResource::collection($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), $exception->getTrace());
            throw new ApiException(message: 'CANT_GET_PERMISSIONS', status: Response::HTTP_BAD_REQUEST);
        }
    }
}
