<?php

namespace Database\Seeders;

use App\Actions\GenerateWorkOrderNumber;
use App\Actions\SeedDefaultCompanyRoles;
use App\Enums\FinancialTransactionType;
use App\Enums\InventoryMovementType;
use App\Enums\SlaStatus;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\FinancialTransaction;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Rating;
use App\Models\SlaPolicy;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\SlaService;
use App\Services\StockService;
use App\Services\WorkOrderStatusService;
use App\Support\CurrentCompany;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Populates one fully working demo company (§53-54) so a first-time
 * evaluator of the product can log in and see every module already
 * populated with realistic data, instead of a wall of empty states.
 * Every value passed to a factory here is explicit (company_id, dates,
 * statuses) rather than relying on CurrentCompany auto-fill — a seeder
 * must be deterministic regardless of what ran before it.
 *
 * Not run automatically by the default `db:seed` — call it explicitly:
 * `php artisan db:seed --class=DemoDataSeeder`. Prints the demo login
 * credentials to the console when run interactively.
 */
class DemoDataSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'demo12345';

    public function run(): void
    {
        $company = Company::factory()->create([
            'name' => 'Operix Demo Ltda',
            'trading_name' => 'Operix Demo',
            'email' => 'contato@demo.operix.test',
            'status' => 'active',
        ]);

        // assignRole() below resolves each role through the current
        // permission "team" (spatie/laravel-permission's teams feature),
        // which defaults to CurrentCompany when not explicitly pinned —
        // without this, role assignment would look for "admin" etc. under
        // no team at all and fail, even though every factory call in this
        // seeder passes company_id explicitly.
        CurrentCompany::set($company->id);

        (new SeedDefaultCompanyRoles)->handle($company);

        $users = $this->seedUsers($company);
        $technicians = $this->seedTechnicians($company, $users);
        $this->seedTeam($company, $technicians);
        [$categories, $products] = $this->seedInventory($company, $users['admin']);
        $slaPolicies = $this->seedSlaPolicies($company);
        $customers = $this->seedCustomers($company);
        $workOrders = $this->seedWorkOrders($company, $customers, $technicians, $slaPolicies, $products, $users);
        $this->seedAppointments($company, $workOrders, $technicians);
        $this->seedFinancials($company, $workOrders, $users['financial']);

        if ($this->command !== null) {
            $this->command->info('Empresa demo criada: '.$company->name);
            $this->command->table(['Papel', 'E-mail', 'Senha'], collect($users)->map(
                fn (User $user, string $role) => [$role, $user->email, self::DEMO_PASSWORD]
            )->values()->all());
        }
    }

    /**
     * @return array<string, User>
     */
    protected function seedUsers(Company $company): array
    {
        $roles = ['admin', 'manager', 'dispatcher', 'technician', 'financial', 'support'];
        $users = [];

        foreach ($roles as $role) {
            $user = User::create([
                'company_id' => $company->id,
                'name' => 'Demo '.ucfirst($role),
                'email' => "{$role}@demo.operix.test",
                'password' => Hash::make(self::DEMO_PASSWORD),
                'status' => 'active',
            ]);
            $user->assignRole($role);
            $users[$role] = $user;
        }

        return $users;
    }

    /**
     * @param  array<string, User>  $users
     * @return Collection<int, Technician>
     */
    protected function seedTechnicians(Company $company, array $users): Collection
    {
        $primary = Technician::factory()->create([
            'company_id' => $company->id,
            'user_id' => $users['technician']->id,
            'name' => $users['technician']->name,
            'status' => 'available',
        ]);

        $others = Technician::factory()->count(3)->create(['company_id' => $company->id]);

        return collect([$primary])->merge($others);
    }

    protected function seedTeam(Company $company, Collection $technicians): void
    {
        $team = Team::factory()->create([
            'company_id' => $company->id,
            'supervisor_id' => $technicians->first()->id,
            'name' => 'Equipe Zona Sul',
        ]);

        $team->technicians()->attach($technicians->pluck('id'));
    }

    /**
     * @return array{0: Collection<int, ProductCategory>, 1: Collection<int, Product>}
     */
    protected function seedInventory(Company $company, User $admin): array
    {
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Distribuidora Central']);

        $categoryNames = ['Redes', 'Elétrica', 'Hidráulica'];
        $categories = collect($categoryNames)->map(
            fn (string $name) => ProductCategory::factory()->create(['company_id' => $company->id, 'name' => $name])
        );

        $products = collect();
        $stockService = app(StockService::class);

        foreach ([
            ['name' => 'Cabo de rede Cat6 (rolo)', 'sku' => 'CAT6-ROLO', 'unit' => 'un', 'stock' => 40, 'min' => 10],
            ['name' => 'Roteador Wi-Fi AC1200', 'sku' => 'RT-AC1200', 'unit' => 'un', 'stock' => 3, 'min' => 5],
            ['name' => 'Disjuntor bipolar 32A', 'sku' => 'DISJ-32A', 'unit' => 'un', 'stock' => 25, 'min' => 8],
            ['name' => 'Conector RJ45', 'sku' => 'RJ45', 'unit' => 'cx', 'stock' => 2, 'min' => 5],
            ['name' => 'Registro de esfera 3/4"', 'sku' => 'REG-34', 'unit' => 'un', 'stock' => 18, 'min' => 5],
        ] as $i => $data) {
            $product = Product::create([
                'company_id' => $company->id,
                'product_category_id' => $categories[$i % $categories->count()]->id,
                'supplier_id' => $supplier->id,
                'name' => $data['name'],
                'sku' => $data['sku'],
                'unit' => $data['unit'],
                'stock_quantity' => $data['stock'],
                'min_stock' => $data['min'],
                'cost_price' => fake()->randomFloat(2, 10, 150),
                'sale_price' => fake()->randomFloat(2, 20, 300),
                'status' => 'active',
            ]);

            $stockService->registerMovement($product, InventoryMovementType::In, (float) $data['stock'], $admin, 'Estoque inicial de demonstração');

            $products->push($product);
        }

        return [$categories, $products];
    }

    /**
     * @return Collection<string, SlaPolicy>
     */
    protected function seedSlaPolicies(Company $company): Collection
    {
        return collect(WorkOrderPriority::cases())->map(function (WorkOrderPriority $priority) use ($company) {
            [$response, $resolution] = match ($priority) {
                WorkOrderPriority::Critical => [15, 120],
                WorkOrderPriority::Urgent => [30, 240],
                WorkOrderPriority::High => [60, 480],
                WorkOrderPriority::Medium => [120, 1440],
                WorkOrderPriority::Low => [240, 4320],
            };

            return SlaPolicy::factory()->create([
                'company_id' => $company->id,
                'name' => 'SLA '.$priority->label(),
                'priority' => $priority->value,
                'response_time_minutes' => $response,
                'resolution_time_minutes' => $resolution,
            ]);
        })->keyBy(fn (SlaPolicy $policy) => $policy->priority->value);
    }

    /**
     * @return Collection<int, Customer>
     */
    protected function seedCustomers(Company $company): Collection
    {
        return Customer::factory()->count(12)->create(['company_id' => $company->id])
            ->each(function (Customer $customer) {
                CustomerAddress::factory()->create(['customer_id' => $customer->id, 'is_primary' => true]);
            });
    }

    /**
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, Technician>  $technicians
     * @param  Collection<string, SlaPolicy>  $slaPolicies
     * @param  Collection<int, Product>  $products
     * @param  array<string, User>  $users
     * @return Collection<int, WorkOrder>
     */
    protected function seedWorkOrders(
        Company $company,
        Collection $customers,
        Collection $technicians,
        Collection $slaPolicies,
        Collection $products,
        array $users,
    ): Collection {
        $statusService = app(WorkOrderStatusService::class);
        $slaService = app(SlaService::class);
        $stockService = app(StockService::class);
        $numberGenerator = app(GenerateWorkOrderNumber::class);

        $blueprints = [
            ['status' => WorkOrderStatus::New, 'priority' => WorkOrderPriority::Medium],
            ['status' => WorkOrderStatus::Scheduled, 'priority' => WorkOrderPriority::High],
            ['status' => WorkOrderStatus::Assigned, 'priority' => WorkOrderPriority::Urgent],
            ['status' => WorkOrderStatus::InProgress, 'priority' => WorkOrderPriority::High, 'overdue' => true],
            ['status' => WorkOrderStatus::WaitingMaterial, 'priority' => WorkOrderPriority::Medium],
            ['status' => WorkOrderStatus::Resolved, 'priority' => WorkOrderPriority::Low],
            ['status' => WorkOrderStatus::Completed, 'priority' => WorkOrderPriority::Medium, 'rated' => true],
            ['status' => WorkOrderStatus::Completed, 'priority' => WorkOrderPriority::Critical, 'rated' => true],
            ['status' => WorkOrderStatus::Cancelled, 'priority' => WorkOrderPriority::Low],
        ];

        $workOrders = collect();

        foreach ($blueprints as $blueprint) {
            $customer = $customers->random();
            $technician = $technicians->random();
            $slaPolicy = $slaPolicies[$blueprint['priority']->value];

            $workOrder = WorkOrder::create([
                'company_id' => $company->id,
                'number' => $numberGenerator->handle($company->id),
                'customer_id' => $customer->id,
                'customer_address_id' => $customer->addresses()->value('id'),
                'technician_id' => $blueprint['status'] === WorkOrderStatus::New ? null : $technician->id,
                'sla_policy_id' => $slaPolicy->id,
                'category' => fake()->randomElement(['Instalação', 'Manutenção', 'Reparo', 'Vistoria']),
                'description' => fake()->sentence(10),
                'priority' => $blueprint['priority']->value,
                'status' => WorkOrderStatus::New->value,
                'origin' => 'manual',
                'created_by' => $users['dispatcher']->id,
            ]);

            // created_at isn't mass-assignable (by design — it's a factual
            // timestamp, not user input), so it's set directly here to
            // stagger the demo work orders across the last couple of weeks.
            $workOrder->created_at = now()->subDays(fake()->numberBetween(1, 10));
            $workOrder->sla_due_at = $slaService->calculateDueDate($workOrder);
            $workOrder->save();
            $statusService->recordCreation($workOrder, $users['dispatcher']);

            foreach ($this->pathTo($blueprint['status']) as $step) {
                $statusService->transition($workOrder, $step, $users['dispatcher']);
            }

            if (! empty($blueprint['overdue'])) {
                $workOrder->sla_due_at = now()->subHours(3);
                $workOrder->sla_status = SlaStatus::Breached;
                $workOrder->save();
            }

            $workOrder->items()->create([
                'description' => 'Mão de obra técnica',
                'quantity' => 1,
                'unit_price' => 180,
                'total_price' => 180,
            ]);

            if (in_array($blueprint['status'], [WorkOrderStatus::Resolved, WorkOrderStatus::Completed], true)) {
                $stockService->consumeForWorkOrder($workOrder, $products->random(), 2, $technician->user ?? $users['technician']);
            }

            if (! empty($blueprint['rated'])) {
                Rating::create([
                    'company_id' => $company->id,
                    'work_order_id' => $workOrder->id,
                    'customer_id' => $customer->id,
                    'technician_id' => $technician->id,
                    'score' => fake()->numberBetween(4, 5),
                    'comment' => 'Atendimento rápido e resolveu o problema.',
                ]);
            }

            $workOrders->push($workOrder->fresh());
        }

        return $workOrders;
    }

    /**
     * @return array<int, WorkOrderStatus>
     */
    protected function pathTo(WorkOrderStatus $target): array
    {
        return match ($target) {
            WorkOrderStatus::New => [],
            WorkOrderStatus::Scheduled => [WorkOrderStatus::Triage, WorkOrderStatus::WaitingScheduling, WorkOrderStatus::Scheduled],
            WorkOrderStatus::Assigned => [WorkOrderStatus::Triage, WorkOrderStatus::WaitingScheduling, WorkOrderStatus::Scheduled, WorkOrderStatus::Assigned],
            WorkOrderStatus::InProgress => [WorkOrderStatus::Triage, WorkOrderStatus::WaitingScheduling, WorkOrderStatus::Scheduled, WorkOrderStatus::Assigned, WorkOrderStatus::EnRoute, WorkOrderStatus::InProgress],
            WorkOrderStatus::WaitingMaterial => [...$this->pathTo(WorkOrderStatus::InProgress), WorkOrderStatus::WaitingMaterial],
            WorkOrderStatus::Resolved => [...$this->pathTo(WorkOrderStatus::InProgress), WorkOrderStatus::Resolved],
            WorkOrderStatus::Completed => [...$this->pathTo(WorkOrderStatus::Resolved), WorkOrderStatus::Completed],
            WorkOrderStatus::Cancelled => [WorkOrderStatus::Cancelled],
            default => [],
        };
    }

    /**
     * @param  Collection<int, WorkOrder>  $workOrders
     * @param  Collection<int, Technician>  $technicians
     */
    protected function seedAppointments(Company $company, Collection $workOrders, Collection $technicians): void
    {
        $schedulable = $workOrders->whereIn('status', [WorkOrderStatus::Scheduled, WorkOrderStatus::Assigned]);

        foreach ($schedulable as $workOrder) {
            Appointment::factory()->create([
                'company_id' => $company->id,
                'work_order_id' => $workOrder->id,
                'technician_id' => $workOrder->technician_id,
                'scheduled_start' => now()->addDays(fake()->numberBetween(1, 5))->setTime(9, 0),
                'scheduled_end' => now()->addDays(fake()->numberBetween(1, 5))->setTime(11, 0),
            ]);
        }
    }

    /**
     * @param  Collection<int, WorkOrder>  $workOrders
     */
    protected function seedFinancials(Company $company, Collection $workOrders, User $financialUser): void
    {
        $completed = $workOrders->where('status', WorkOrderStatus::Completed);

        foreach ($completed as $workOrder) {
            FinancialTransaction::create([
                'company_id' => $company->id,
                'work_order_id' => $workOrder->id,
                'customer_id' => $workOrder->customer_id,
                'type' => FinancialTransactionType::Cost->value,
                'category' => 'Deslocamento',
                'description' => 'Combustível e pedágio',
                'amount' => fake()->randomFloat(2, 20, 80),
                'occurred_at' => $workOrder->completed_at ?? now(),
                'created_by' => $financialUser->id,
            ]);
        }

        FinancialTransaction::create([
            'company_id' => $company->id,
            'type' => FinancialTransactionType::Revenue->value,
            'category' => 'Contrato',
            'description' => 'Contrato de manutenção preventiva mensal',
            'amount' => 1500,
            'occurred_at' => now()->subDays(3),
            'created_by' => $financialUser->id,
        ]);
    }
}
