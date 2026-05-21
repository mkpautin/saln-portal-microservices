<?php

namespace App\Http\Controllers;

use App\Models\SalnForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SalnFormController extends Controller
{
    public function edit(): JsonResponse
    {
        $salnForm = $this->findSalnForm($this->userIdFromAuth());

        return response()->json([
            'data' => $this->exportPayload($salnForm),
        ]);
    }

    public function export(): JsonResponse
    {
        $salnForm = $this->findSalnForm($this->userIdFromAuth());

        return response()->json([
            'filename' => 'saln-progress.json',
            'data' => $this->exportPayload($salnForm),
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'import_file' => ['required', 'file', 'mimetypes:application/json,text/plain', 'max:2048'],
        ]);

        $raw = file_get_contents($validated['import_file']->getRealPath());

        if ($raw === false) {
            throw ValidationException::withMessages([
                'import_file' => 'Unable to read the imported file.',
            ]);
        }

        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                'import_file' => 'The file does not contain valid JSON.',
            ]);
        }

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'import_file' => 'The imported JSON must be an object.',
            ]);
        }

        $normalizedPayload = $this->normalizeImportedPayload($payload);
        $validatedPayload = Validator::make($normalizedPayload, $this->rules(true))->validate();

        $userId = $this->userIdFromAuth();
        $this->storePayload($userId, $validatedPayload);
        $salnForm = $this->findSalnForm($userId);

        return response()->json([
            'message' => 'SALN JSON imported successfully.',
            'data' => $this->exportPayload($salnForm),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $normalized = $this->normalizeDraftPayload($request->all());
        $validated = Validator::make($normalized, $this->rules(true))->validate();

        $this->storePayload($this->userIdFromAuth(), $validated);

        return response()->json([
            'status' => 'saved',
        ]);
    }

    private function rules(bool $draft = false): array
    {
        $currentYear = (int) now()->format('Y');
        $complianceTypeRules = $draft ? ['nullable', 'in:assumption,annual,exit'] : ['required', 'in:assumption,annual,exit'];
        $assumptionRules = $draft ? ['nullable', 'date'] : ['nullable', 'date', 'required_if:compliance_type,assumption'];
        $annualRules = $draft ? ['nullable', 'integer', 'between:1900,'.($currentYear + 1)] : ['nullable', 'integer', 'between:1900,'.($currentYear + 1), 'required_if:compliance_type,annual'];
        $exitRules = $draft ? ['nullable', 'date'] : ['nullable', 'date', 'required_if:compliance_type,exit'];
        $requiredTextRules = $draft ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'];
        $requiredAddressRules = $draft ? ['nullable', 'string', 'max:500'] : ['required', 'string', 'max:500'];
        $filingTypeRules = $draft ? ['nullable', 'in:joint,separate,not_applicable'] : ['required', 'in:joint,separate,not_applicable'];

        return [
            'compliance_type' => $complianceTypeRules,
            'assumption_date' => $assumptionRules,
            'annual_year' => $annualRules,
            'exit_date' => $exitRules,

            'declarant.family_name' => $requiredTextRules,
            'declarant.first_name' => $requiredTextRules,
            'declarant.middle_initial' => ['nullable', 'string', 'max:5'],
            'declarant.position' => $requiredTextRules,
            'declarant.agency_office' => $requiredTextRules,
            'declarant.office_address' => $requiredAddressRules,

            'spouse.family_name' => $requiredTextRules,
            'spouse.first_name' => $requiredTextRules,
            'spouse.middle_initial' => ['nullable', 'string', 'max:5'],
            'spouse.position' => $requiredTextRules,
            'spouse.agency_office' => $requiredTextRules,
            'spouse.office_address' => $requiredAddressRules,

            'filing_type' => $filingTypeRules,

            'additional_spouses' => ['nullable', 'array'],
            'additional_spouses.*.name' => ['nullable', 'string', 'max:255'],

            'children' => ['nullable', 'array'],
            'children.*.name' => ['nullable', 'string', 'max:255'],
            'children.*.date_of_birth' => ['nullable', 'date'],

            'real_properties' => ['nullable', 'array'],
            'real_properties.*.description' => ['nullable', 'string', 'max:255'],
            'real_properties.*.kind' => ['nullable', 'string', 'max:255'],
            'real_properties.*.exact_location' => ['nullable', 'string', 'max:500'],
            'real_properties.*.assessed_value' => ['nullable', 'numeric', 'min:0'],
            'real_properties.*.current_fair_market_value' => ['nullable', 'numeric', 'min:0'],
            'real_properties.*.year_of_acquisition' => ['nullable', 'string', 'max:255'],
            'real_properties.*.mode_of_acquisition' => ['nullable', 'string', 'max:255'],
            'real_properties.*.acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'real_properties.*.owner_scope' => ['nullable', 'in:declarant,spouse_children'],

            'personal_properties' => ['nullable', 'array'],
            'personal_properties.*.description' => ['nullable', 'string', 'max:255'],
            'personal_properties.*.acquisition_year' => ['nullable', 'string', 'max:255'],
            'personal_properties.*.acquisition_cost_amount' => ['nullable', 'numeric', 'min:0'],
            'personal_properties.*.owner_scope' => ['nullable', 'in:declarant,spouse_children'],

            'business_interests' => ['nullable', 'array'],
            'business_interests.*.name_of_entity_or_business_enterprise' => ['nullable', 'string', 'max:255'],
            'business_interests.*.business_address' => ['nullable', 'string', 'max:500'],
            'business_interests.*.nature_of_business_interest_or_financial_connection' => ['nullable', 'string', 'max:500'],
            'business_interests.*.date_of_acquisition' => ['nullable', 'date'],
            'business_interests.*.owner_scope' => ['nullable', 'in:declarant,spouse_children'],

            'relatives_in_government_service' => ['nullable', 'array'],
            'relatives_in_government_service.*.name_of_relative' => ['nullable', 'string', 'max:255'],
            'relatives_in_government_service.*.relationship' => ['nullable', 'string', 'max:255'],
            'relatives_in_government_service.*.position' => ['nullable', 'string', 'max:255'],
            'relatives_in_government_service.*.name_of_agency_office_and_address' => ['nullable', 'string', 'max:500'],

            'liabilities' => ['nullable', 'array'],
            'liabilities.*.nature' => ['nullable', 'string', 'max:255'],
            'liabilities.*.name_of_creditor' => ['nullable', 'string', 'max:255'],
            'liabilities.*.outstanding_balance' => ['nullable', 'numeric', 'min:0'],
            'liabilities.*.owner_scope' => ['nullable', 'in:declarant,spouse_children'],
        ];
    }

    private function exportPayload(?SalnForm $salnForm): array
    {
        if (! $salnForm) {
            return [
                'compliance_type' => 'assumption',
                'assumption_date' => null,
                'annual_year' => null,
                'exit_date' => null,
                'declarant' => [],
                'spouse' => [],
                'filing_type' => 'joint',
                'additional_spouses' => [],
                'children' => [],
                'real_properties' => [],
                'personal_properties' => [],
                'business_interests' => [],
                'relatives_in_government_service' => [],
                'liabilities' => [],
                'total_assets' => '0.00',
                'total_liabilities' => '0.00',
                'net_worth' => '0.00',
            ];
        }

        return [
            'compliance_type' => $salnForm->compliance_type,
            'assumption_date' => $salnForm->compliance_type === 'assumption' && $salnForm->compliance_date ? $salnForm->compliance_date->format('Y-m-d') : null,
            'annual_year' => $salnForm->compliance_type === 'annual' ? $salnForm->compliance_year : null,
            'exit_date' => $salnForm->compliance_type === 'exit' && $salnForm->compliance_date ? $salnForm->compliance_date->format('Y-m-d') : null,
            'declarant' => $salnForm->declarant ?? [],
            'spouse' => $salnForm->spouse ?? [],
            'filing_type' => $salnForm->filing_type,
            'additional_spouses' => $salnForm->additional_spouses ?? [],
            'children' => $salnForm->children ?? [],
            'real_properties' => $salnForm->real_properties ?? [],
            'personal_properties' => $salnForm->personal_properties ?? [],
            'business_interests' => $salnForm->business_interests ?? [],
            'relatives_in_government_service' => $salnForm->relatives_in_government_service ?? [],
            'liabilities' => $salnForm->liabilities ?? [],
            'total_assets' => $this->formatMoney($salnForm->total_assets ?? 0),
            'total_liabilities' => $this->formatMoney($salnForm->total_liabilities ?? 0),
            'net_worth' => $this->formatMoney($salnForm->net_worth ?? 0),
        ];
    }

    private function normalizeImportedPayload(array $payload): array
    {
        foreach ([
            'additional_spouses',
            'children',
            'real_properties',
            'personal_properties',
            'business_interests',
            'relatives_in_government_service',
            'liabilities',
        ] as $section) {
            $payload[$section] = array_values($payload[$section] ?? []);
        }

        return $payload;
    }

    private function storePayload(int $userId, array $validated): void
    {
        $current = $this->exportPayload($this->findSalnForm($userId));

        $complianceType = ($validated['compliance_type'] ?? '') !== ''
            ? $validated['compliance_type']
            : $current['compliance_type'];
        $filingType = ($validated['filing_type'] ?? '') !== ''
            ? $validated['filing_type']
            : $current['filing_type'];

        $payload = [
            'compliance_type' => $complianceType,
            'assumption_date' => $this->blankToNull($validated['assumption_date'] ?? $current['assumption_date']),
            'annual_year' => $this->blankToNull($validated['annual_year'] ?? $current['annual_year']),
            'exit_date' => $this->blankToNull($validated['exit_date'] ?? $current['exit_date']),
            'declarant' => $validated['declarant'] ?? $current['declarant'],
            'spouse' => $validated['spouse'] ?? $current['spouse'],
            'filing_type' => $filingType,
            'additional_spouses' => array_key_exists('additional_spouses', $validated) ? $validated['additional_spouses'] : $current['additional_spouses'],
            'children' => array_key_exists('children', $validated) ? $validated['children'] : $current['children'],
            'real_properties' => array_key_exists('real_properties', $validated) ? $validated['real_properties'] : $current['real_properties'],
            'personal_properties' => array_key_exists('personal_properties', $validated) ? $validated['personal_properties'] : $current['personal_properties'],
            'business_interests' => array_key_exists('business_interests', $validated) ? $validated['business_interests'] : $current['business_interests'],
            'relatives_in_government_service' => array_key_exists('relatives_in_government_service', $validated) ? $validated['relatives_in_government_service'] : $current['relatives_in_government_service'],
            'liabilities' => array_key_exists('liabilities', $validated) ? $validated['liabilities'] : $current['liabilities'],
        ];

        $additionalSpouses = $this->filterRows($payload['additional_spouses']);
        $children = $this->filterRows($payload['children']);
        $realProperties = $this->filterRows($payload['real_properties']);
        $personalProperties = $this->filterRows($payload['personal_properties']);
        $businessInterests = $this->filterRows($payload['business_interests']);
        $relativesInGovernmentService = $this->filterRows($payload['relatives_in_government_service']);
        $liabilities = $this->filterRows($payload['liabilities']);

        $realProperties = $this->applyOwnerScopeDefault($realProperties);
        $personalProperties = $this->applyOwnerScopeDefault($personalProperties);
        $businessInterests = $this->applyOwnerScopeDefault($businessInterests);
        $liabilities = $this->applyOwnerScopeDefault($liabilities);

        $realSubtotal = collect($realProperties)->sum(static fn (array $item): float => (float) ($item['acquisition_cost'] ?? 0));
        $personalSubtotal = collect($personalProperties)->sum(static fn (array $item): float => (float) ($item['acquisition_cost_amount'] ?? 0));
        $totalAssets = $realSubtotal + $personalSubtotal;

        $totalLiabilities = collect($liabilities)->sum(static fn (array $item): float => (float) ($item['outstanding_balance'] ?? 0));
        $netWorth = $totalAssets - $totalLiabilities;

        $complianceDate = null;
        $complianceYear = null;

        if ($payload['compliance_type'] === 'assumption') {
            $complianceDate = $payload['assumption_date'];
        } elseif ($payload['compliance_type'] === 'annual') {
            $complianceYear = $payload['annual_year'] !== null ? (int) $payload['annual_year'] : null;
        } elseif ($payload['compliance_type'] === 'exit') {
            $complianceDate = $payload['exit_date'];
        }

        SalnForm::updateOrCreate(
            ['user_id' => $userId],
            [
                'compliance_type' => $payload['compliance_type'],
                'compliance_date' => $complianceDate,
                'compliance_year' => $complianceYear,
                'declarant' => $payload['declarant'],
                'spouse' => $payload['spouse'],
                'filing_type' => $payload['filing_type'],
                'additional_spouses' => $additionalSpouses,
                'children' => $children,
                'real_properties' => $realProperties,
                'personal_properties' => $personalProperties,
                'business_interests' => $businessInterests,
                'relatives_in_government_service' => $relativesInGovernmentService,
                'liabilities' => $liabilities,
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'net_worth' => $netWorth,
            ]
        );
    }

    private function blankToNull(mixed $value): mixed
    {
        if ($value === '') {
            return null;
        }

        return $value;
    }

    private function normalizeDraftPayload(array $payload): array
    {
        $normalized = [];

        foreach (['compliance_type', 'assumption_date', 'annual_year', 'exit_date', 'filing_type'] as $field) {
            if (array_key_exists($field, $payload)) {
                $normalized[$field] = $payload[$field];
            }
        }

        foreach (['declarant', 'spouse'] as $section) {
            if (array_key_exists($section, $payload) && is_array($payload[$section])) {
                $normalized[$section] = $payload[$section];
            }
        }

        foreach ([
            'additional_spouses',
            'children',
            'real_properties',
            'personal_properties',
            'business_interests',
            'relatives_in_government_service',
            'liabilities',
        ] as $section) {
            if (array_key_exists($section, $payload) && is_array($payload[$section])) {
                $normalized[$section] = $payload[$section];
            }
        }

        foreach ([
            'additional_spouses',
            'children',
            'real_properties',
            'personal_properties',
            'business_interests',
            'relatives_in_government_service',
            'liabilities',
        ] as $section) {
            if (! array_key_exists($section, $normalized)) {
                continue;
            }

            $normalized[$section] = array_values(array_filter($normalized[$section], static fn ($item): bool => is_array($item)));
        }

        foreach (['real_properties', 'personal_properties', 'business_interests', 'liabilities'] as $ownerScopedSection) {
            if (! array_key_exists($ownerScopedSection, $normalized)) {
                continue;
            }

            $normalized[$ownerScopedSection] = $this->applyOwnerScopeDefault($normalized[$ownerScopedSection]);
        }

        return $normalized;
    }

    private function userIdFromAuth(): int
    {
        $payload = auth()->payload();

        return (int) $payload->get('sub');
    }

    private function findSalnForm(int $userId): ?SalnForm
    {
        return SalnForm::query()->where('user_id', $userId)->first();
    }

    private function formatMoney(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function applyOwnerScopeDefault(array $rows): array
    {
        return array_map(static function (array $row): array {
            $scope = $row['owner_scope'] ?? 'declarant';
            $row['owner_scope'] = in_array($scope, ['declarant', 'spouse_children'], true)
                ? $scope
                : 'declarant';

            return $row;
        }, $rows);
    }

    private function filterRows(array $rows): array
    {
        return array_values(array_filter($rows, static function ($row): bool {
            if (! is_array($row)) {
                return false;
            }

            foreach ($row as $key => $value) {
                if ($key === 'owner_scope') {
                    continue;
                }

                if ($value !== null && $value !== '') {
                    return true;
                }
            }

            return false;
        }));
    }
}
