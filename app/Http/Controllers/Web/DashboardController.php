<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CrmResources;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $groups = [];

        $groups['Geral'] = [[
            'label' => 'Usuários',
            'description' => 'Gerencie usuários e permissões de acesso.',
            'route' => route('web.users.index'),
            'count' => User::count(),
        ]];

        foreach (CrmResources::grouped() as $group => $definitions) {
            foreach ($definitions as $definition) {
                $groups[$group][] = [
                    'label' => $definition->label,
                    'description' => 'Cadastre e gerencie '.mb_strtolower($definition->label).'.',
                    'route' => route($definition->indexRoute()),
                    'count' => $definition->modelClass::count(),
                ];
            }
        }

        return view('dashboard', ['groups' => $groups]);
    }
}
