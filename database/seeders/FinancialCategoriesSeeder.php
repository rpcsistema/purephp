<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinancialCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Revenue Categories
            [
                'name' => 'Vendas',
                'description' => 'Receitas provenientes de vendas de produtos ou serviços',
                'type' => 'revenue',
                'color' => '#10B981',
                'icon' => 'shopping-cart',
                'is_active' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Serviços',
                'description' => 'Receitas de prestação de serviços',
                'type' => 'revenue',
                'color' => '#3B82F6',
                'icon' => 'briefcase',
                'is_active' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Investimentos',
                'description' => 'Receitas de investimentos e aplicações',
                'type' => 'revenue',
                'color' => '#8B5CF6',
                'icon' => 'trending-up',
                'is_active' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Outras Receitas',
                'description' => 'Outras fontes de receita',
                'type' => 'revenue',
                'color' => '#06B6D4',
                'icon' => 'plus-circle',
                'is_active' => true,
                'parent_id' => null,
            ],

            // Expense Categories
            [
                'name' => 'Salários e Encargos',
                'description' => 'Despesas com folha de pagamento e encargos trabalhistas',
                'type' => 'expense',
                'color' => '#EF4444',
                'icon' => 'users',
                'is_active' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Marketing e Publicidade',
                'description' => 'Despesas com marketing, publicidade e propaganda',
                'type' => 'expense',
                'color' => '#F59E0B',
                'icon' => 'speakerphone',
                'is_active' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Tecnologia',
                'description' => 'Despesas com software, hardware e tecnologia',
                'type' => 'expense',
                'color' => '#6366F1',
                'icon' => 'desktop-computer',
                'is_active' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Aluguel e Utilidades',
                'description' => 'Despesas com aluguel, energia, água, internet',
                'type' => 'expense',
                'color' => '#84CC16',
                'icon' => 'home',
                'is_active' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Fornecedores',
                'description' => 'Despesas com fornecedores e matéria-prima',
                'type' => 'expense',
                'color' => '#F97316',
                'icon' => 'truck',
                'is_active' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Impostos e Taxas',
                'description' => 'Despesas com impostos, taxas e tributos',
                'type' => 'expense',
                'color' => '#DC2626',
                'icon' => 'document-text',
                'is_active' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Viagens e Hospedagem',
                'description' => 'Despesas com viagens de negócios e hospedagem',
                'type' => 'expense',
                'color' => '#059669',
                'icon' => 'airplane',
                'is_active' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Outras Despesas',
                'description' => 'Outras despesas operacionais',
                'type' => 'expense',
                'color' => '#6B7280',
                'icon' => 'minus-circle',
                'is_active' => true,
                'parent_id' => null,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('financial_categories')->insert(array_merge($category, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}