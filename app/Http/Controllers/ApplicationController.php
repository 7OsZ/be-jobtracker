<?php

namespace App\Http\Controllers;

use App\Http\Requests\Applications\StoreApplicationRequest;
use App\Http\Requests\Applications\UpdateApplicationRequest;
use App\Http\Resources\ApplicationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ApplicationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ApplicationResource::collection(
            $request->user()->applications()->latest('updated_at')->get()
        );
    }

    public function store(StoreApplicationRequest $request): JsonResponse
    {
        $application = $request->user()->applications()->create($request->validated());

        return (new ApplicationResource($application))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id): ApplicationResource
    {
        // Scoped lookup: a foreign id is a 404, never a 403.
        $application = $request->user()->applications()->findOrFail($id);
        Gate::authorize('view', $application);

        return new ApplicationResource($application);
    }

    public function update(UpdateApplicationRequest $request, int $id): ApplicationResource
    {
        $application = $request->user()->applications()->findOrFail($id);
        Gate::authorize('update', $application);

        $application->update($request->validated());

        return new ApplicationResource($application);
    }

    public function destroy(Request $request, int $id): Response
    {
        $application = $request->user()->applications()->findOrFail($id);
        Gate::authorize('delete', $application);

        $application->delete();

        return response()->noContent();
    }
}
