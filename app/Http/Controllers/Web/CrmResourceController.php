<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\CrmResourceDefinition;
use App\Support\CrmResources;
use App\Support\FormLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CrmResourceController extends Controller
{
    public function index(Request $request): View
    {
        $definition = $this->definition($request);
        Gate::authorize('viewAny', $definition->modelClass);

        $filters = [];

        foreach ($definition->filters as $filter) {
            $filters[$filter['name']] = (string) $request->query($filter['name'], '');
        }

        $search = trim((string) $request->query('q', ''));

        $query = $definition->modelClass::query();

        if ($search !== '' && $definition->searchable !== []) {
            $query->where(function ($query) use ($definition, $search): void {
                foreach ($definition->searchable as $index => $field) {
                    $index === 0
                        ? $query->where($field, 'like', "%{$search}%")
                        : $query->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        foreach ($definition->filters as $filter) {
            $value = $filters[$filter['name']];

            if ($value === '') {
                continue;
            }

            if (isset($filter['where'])) {
                $filter['where']($query, $value);
            } else {
                $query->where($filter['name'], $value);
            }
        }

        if ($definition->with !== []) {
            $query->with($definition->with);
        }

        $records = $query
            ->orderBy($definition->orderBy, $definition->orderDirection)
            ->paginate(15)
            ->withQueryString();

        return view('crm.index', [
            'definition' => $definition,
            'records' => $records,
            'filters' => $filters,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        $definition = $this->definition($request);
        Gate::authorize('create', $definition->modelClass);

        return view('crm.form', [
            'definition' => $definition,
            'record' => null,
            'limits' => FormLimits::from($request, $definition->storeRequestClass),
            'numericLimits' => FormLimits::numericMaxFrom($request, $definition->storeRequestClass),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $definition = $this->definition($request);
        Gate::authorize('create', $definition->modelClass);

        $data = $this->validatedData($request, $definition->storeRequestClass);

        foreach ($definition->seed as $key => $value) {
            if (isset($data[$key]) && $data[$key] !== '') {
                continue;
            }

            $data[$key] = is_callable($value) ? $value() : $value;
        }

        $definition->modelClass::create($data);

        $participle = $definition->participle('criado', 'criada');

        return redirect()->route($definition->indexRoute())
            ->with('status', "{$definition->singularLabel} {$participle} com sucesso.");
    }

    public function show(Request $request, int|string $record): View
    {
        $definition = $this->definition($request);
        $query = $definition->modelClass::query();

        if ($definition->with !== []) {
            $query->with($definition->with);
        }

        $model = $query->findOrFail($record);
        Gate::authorize('view', $model);

        return view('crm.show', [
            'definition' => $definition,
            'record' => $model,
        ]);
    }

    public function edit(Request $request, int|string $record): View
    {
        $definition = $this->definition($request);
        $model = $definition->modelClass::findOrFail($record);
        Gate::authorize('update', $model);

        return view('crm.form', [
            'definition' => $definition,
            'record' => $model,
            'limits' => FormLimits::from($request, $definition->updateRequestClass),
            'numericLimits' => FormLimits::numericMaxFrom($request, $definition->updateRequestClass),
        ]);
    }

    public function update(Request $request, int|string $record): RedirectResponse
    {
        $definition = $this->definition($request);
        $model = $definition->modelClass::findOrFail($record);
        Gate::authorize('update', $model);

        $request->route()->setParameter($definition->routeParameter(), $record);
        $data = $this->validatedData($request, $definition->updateRequestClass);

        $original = $model->getOriginal();

        DB::transaction(function () use ($definition, $model, $data, $request, $original): void {
            $model->update($data);

            if ($definition->afterUpdate !== null) {
                ($definition->afterUpdate)($model, $data, $request, $original);
            }
        });

        $participle = $definition->participle('atualizado', 'atualizada');

        return redirect()->route($definition->indexRoute())
            ->with('status', "{$definition->singularLabel} {$participle} com sucesso.");
    }

    public function destroy(Request $request, int|string $record): RedirectResponse
    {
        $definition = $this->definition($request);
        $model = $definition->modelClass::findOrFail($record);
        Gate::authorize('delete', $model);

        $dependencies = [];

        foreach ($definition->dependencies as $dependency) {
            $query = $dependency['model']::query();

            if (method_exists($dependency['model'], 'withTrashed')) {
                $query->withTrashed();
            }

            if ($query->where($dependency['column'], $model->getKey())->exists()) {
                $dependencies[] = $dependency['label'];
            }
        }

        if ($dependencies !== []) {
            $message = $definition->dependencyMessage
                ?? 'Não é possível remover este registro porque existem '.implode(', ', $dependencies).' vinculados.';

            return redirect()->route($definition->indexRoute())
                ->with('error', $message);
        }

        $model->delete();

        $participle = $definition->participle('removido', 'removida');

        return redirect()->route($definition->indexRoute())
            ->with('status', "{$definition->singularLabel} {$participle} com sucesso.");
    }

    private function definition(Request $request): CrmResourceDefinition
    {
        $routeName = (string) $request->route()?->getName();

        if (! str_starts_with($routeName, 'web.')) {
            throw new NotFoundHttpException('Recurso não encontrado.');
        }

        $key = explode('.', $routeName)[1] ?? '';

        if (! CrmResources::has($key)) {
            throw new NotFoundHttpException('Recurso não encontrado.');
        }

        return CrmResources::get($key);
    }

    private function validatedData(Request $request, string $formRequestClass): array
    {
        $formRequest = $formRequestClass::createFrom($request);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->validateResolved();

        return $formRequest->validated();
    }
}
