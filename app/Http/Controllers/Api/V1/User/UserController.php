<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\User\UserHistoryResource;
use App\Http\Resources\V1\User\UserQueryResource;
use App\Http\Resources\V1\User\UserResource;
use App\Models\User;
use App\Models\UserHistory;
use App\QueryFilters\DateFilter;
use App\QueryFilters\UserPermissionFilter;
use App\QueryFilters\UserRoleFilter;
use App\QueryFilters\UserWithEmailFilter;
use App\QuerySorts\UserWithEmailSort;
use App\Services\User\UserService;
use App\Utils\Response\ResponseUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    protected function allowedSorts(): array
    {
        return [
            AllowedSort::field('id', 'id'),
            AllowedSort::custom('fullName', new UserWithEmailSort),
            AllowedSort::field('email', 'email'),
            AllowedSort::field('createdAt', 'created_at'),
            AllowedSort::field('updatedAt', 'updated_at'),
            AllowedSort::field('deletedAt', 'deleted_at'),
            AllowedSort::field('emailVerifiedAt', 'email_verified_at'),
        ];
    }

    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('id', 'id'),
            AllowedFilter::custom('fullName', new UserWithEmailFilter),
            AllowedFilter::partial('email', 'email'),
            AllowedFilter::custom('createdAt', new DateFilter),
            AllowedFilter::custom('updatedAt', new DateFilter),
            AllowedFilter::custom('deletedAt', new DateFilter),
            AllowedFilter::custom('emailVerifiedAt', new DateFilter),
            AllowedFilter::callback('emailVerifiedAtNo', function ($query, $value) {
                if ($value) {
                    $query->whereNull('email_verified_at');
                }
            }),
            AllowedFilter::callback('emailVerifiedAtYes', function ($query, $value) {
                if ($value) {
                    $query->whereNotNull('email_verified_at');
                }
            }),
            AllowedFilter::custom('role', new UserRoleFilter),
            AllowedFilter::custom('permission', new UserPermissionFilter),
        ];
    }

    public function getAll(Request $request): JsonResponse
    {
        $data = QueryBuilder::for(User::class)
            ->defaultSort('email')
            ->allowedSorts($this->allowedSorts())
            ->allowedFilters($this->allowedFilters())
            ->get();

        return ResponseUtils::response(data: UserResource::collection($data));
    }

    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->pageSize ?? config('query-builder.default_page_size');

        $data = QueryBuilder::for(User::class)
            ->defaultSort('email')
            ->allowedSorts($this->allowedSorts())
            ->allowedFilters($this->allowedFilters())
            ->paginate($pageSize);

        return ResponseUtils::response(data: new UserQueryResource($data));
    }

    public function queryDeleted(Request $request): JsonResponse
    {
        $pageSize = $request->pageSize ?? config('query-builder.default_page_size');

        $data = QueryBuilder::for(User::onlyTrashed())
            ->defaultSort('email')
            ->allowedSorts($this->allowedSorts())
            ->allowedFilters($this->allowedFilters())
            ->paginate($pageSize);

        return ResponseUtils::response(data: new UserQueryResource($data));
    }

    public function getById(Request $request, string $userId): JsonResponse
    {
        $data = User::findOrFail($userId);

        return ResponseUtils::response(data: new UserResource($data));
    }

    public function getHistory(Request $request, User $user): JsonResponse
    {
        try {
            $data = UserHistory::where('user_id', '=', $user->id)->orderBy('id', 'desc')->get();

            return ResponseUtils::response(data: UserHistoryResource::collection($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_GET_HISTORY', status: Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $request->merge([
            'locale' => $request->header('Locale', 'pl'),
        ]);

        $validated = $request->validate([
            'firstName' => ['required', 'string', 'max:191'],
            'lastName' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', Rule::unique('users')],
            'confirmed' => ['required', 'boolean'],
            'roles' => ['nullable', 'array', 'size:1'],
            'roles.*' => [Rule::in(['student', 'teacher'])],
            'locale' => ['required', Rule::in(['pl', 'es', 'en'])],
        ]);

        try {
            $data = $this->userService->create(data: $validated);

            return ResponseUtils::response(data: new UserResource($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_CREATE_USER', status: Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(Request $request, string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'firstName' => ['required', 'string', 'max:191'],
            'lastName' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', Rule::unique('users', 'email')->ignore($userId)],
            'confirmed' => ['required', 'boolean'],
        ]);

        try {
            $data = $this->userService->update(user: $user, data: $validated);

            return ResponseUtils::response(data: new UserResource($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_UPDATE_USER', status: Response::HTTP_BAD_REQUEST);
        }
    }

    public function changePassword(Request $request, string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        try {
            $data = $this->userService->changePassword(user: $user, data: $validated);

            return ResponseUtils::response(data: new UserResource($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_CHANGE_USER_PASSWORD', status: Response::HTTP_BAD_REQUEST);
        }
    }

    public function delete(Request $request, string $userId): JsonResponse
    {
        try {
            $data = $this->userService->delete(userId: $userId);

            return ResponseUtils::response(data: new UserResource($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_DELETE_USER', status: Response::HTTP_BAD_REQUEST);
        }
    }

    public function restore(Request $request, string $userId): JsonResponse
    {
        try {
            $data = $this->userService->restore(userId: $userId);

            return ResponseUtils::response(data: new UserResource($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_RESTORE_USER', status: Response::HTTP_BAD_REQUEST);
        }
    }

    public function attachRoles(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            $selectedRolesIds = $request->selectedRolesIds;

            $data = $this->userService->attachRoles(user: $user, selectedRolesIds: $selectedRolesIds);

            return ResponseUtils::response(data: new UserResource($data));
        } catch (ApiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            throw new ApiException(message: 'CANT_ATTACH_ROLE_USER', status: Response::HTTP_BAD_REQUEST);
        }
    }
}
