<?php

namespace App\Services\SalnPdf;

use App\Models\SalnForm;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class SalnPdfGeneratorService
{
    private const OWNER_DECLARANT = 'declarant';

    private const OWNER_SPOUSE_CHILDREN = 'spouse_children';

    public function generate(SalnForm $salnForm): array
    {
        $templatePath = base_path('storage/app/pdf-templates/saln_fillable_form.pdf');

        if (! is_file($templatePath) || ! is_readable($templatePath)) {
            throw new RuntimeException('PDF template unavailable. Please contact support.');
        }

        $tempDir = sys_get_temp_dir().'/saln-pdf/'.Str::uuid()->toString();
        $this->ensureDirectory($tempDir);

        try {
            return $this->buildPdf($salnForm, $templatePath, $tempDir);
        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    private function buildPdf(SalnForm $salnForm, string $templatePath, string $tempDir): array
    {
        $annexATemplate = $tempDir.'/annex_a_template.pdf';
        $annexBTemplate = $tempDir.'/annex_b_template.pdf';
        $annexCTemplate = $tempDir.'/annex_c_template.pdf';

        $this->runPdftk([$templatePath, 'cat', '1-2', 'output', $annexATemplate]);
        $this->runPdftk([$templatePath, 'cat', '3', 'output', $annexBTemplate]);
        $this->runPdftk([$templatePath, 'cat', '4', 'output', $annexCTemplate]);

        $partitioned = $this->partitionRows($salnForm);
        $annexBPageCount = count($partitioned['annex_b_pages']);
        $annexCPageCount = count($partitioned['annex_c_pages']);
        $totalPages = 2 + $annexBPageCount + $annexCPageCount;

        $pagesToMerge = [];

        $annexAFieldMap = $this->buildAnnexAFieldMap($salnForm, $partitioned, $totalPages);
        $annexAOutput = $tempDir.'/annex_a_filled.pdf';
        $this->fillAndFlatten($annexATemplate, $annexAFieldMap, $annexAOutput, $tempDir.'/annex_a');
        $pagesToMerge[] = $annexAOutput;

        foreach ($partitioned['annex_b_pages'] as $index => $pageData) {
            $fieldMap = $this->buildAnnexBFieldMap($salnForm, $pageData, $index + 1, $annexBPageCount);
            $output = $tempDir.'/annex_b_'.($index + 1).'.pdf';
            $this->fillAndFlatten($annexBTemplate, $fieldMap, $output, $tempDir.'/annex_b_'.($index + 1));
            $pagesToMerge[] = $output;
        }

        foreach ($partitioned['annex_c_pages'] as $index => $pageData) {
            $fieldMap = $this->buildAnnexCFieldMap($salnForm, $pageData, $index + 1, $annexCPageCount);
            $output = $tempDir.'/annex_c_'.($index + 1).'.pdf';
            $this->fillAndFlatten($annexCTemplate, $fieldMap, $output, $tempDir.'/annex_c_'.($index + 1));
            $pagesToMerge[] = $output;
        }

        $fileToken = now()->format('Ymd-His-u').'-'.Str::lower((string) Str::ulid());
        $outputPath = sys_get_temp_dir().'/saln-'.(int) $salnForm->user_id.'-'.$fileToken.'.pdf';
        $this->ensureDirectory(dirname($outputPath));

        $mergeArgs = array_merge($pagesToMerge, ['cat', 'output', $outputPath]);
        $this->runPdftk($mergeArgs);

        return [
            'path' => $outputPath,
            'filename' => 'saln-'.$fileToken.'.pdf',
            'annex_b_pages' => $annexBPageCount,
            'annex_c_pages' => $annexCPageCount,
            'total_pages' => $totalPages,
        ];
    }

    private function partitionRows(SalnForm $salnForm): array
    {
        $sections = [
            'real_properties' => ['annex_a_capacity' => 4, 'annex_x_capacity' => 4],
            'personal_properties' => ['annex_a_capacity' => 6, 'annex_x_capacity' => 4],
            'liabilities' => ['annex_a_capacity' => 4, 'annex_x_capacity' => 4],
            'business_interests' => ['annex_a_capacity' => 3, 'annex_x_capacity' => 3],
        ];

        $normalized = [];
        $annexASections = [];
        $overflowDeclarant = [];
        $overflowSpouseChildren = [];

        foreach ($sections as $section => $config) {
            $rows = $this->normalizeRows($salnForm->{$section} ?? []);
            $normalized[$section] = $rows;

            $annexASections[$section] = array_slice($rows, 0, $config['annex_a_capacity']);
            $overflowRows = array_slice($rows, $config['annex_a_capacity']);

            $overflowDeclarant[$section] = array_values(array_filter(
                $overflowRows,
                fn (array $row): bool => ($row['owner_scope'] ?? self::OWNER_DECLARANT) === self::OWNER_DECLARANT
            ));

            $overflowSpouseChildren[$section] = array_values(array_filter(
                $overflowRows,
                fn (array $row): bool => ($row['owner_scope'] ?? self::OWNER_DECLARANT) === self::OWNER_SPOUSE_CHILDREN
            ));
        }

        $annexBPages = $this->chunkOverflowPages($overflowDeclarant, $sections);
        $annexCPages = $this->chunkOverflowPages($overflowSpouseChildren, $sections);

        return [
            'annex_a' => $annexASections,
            'annex_b_pages' => $annexBPages,
            'annex_c_pages' => $annexCPages,
            'all_sections' => $normalized,
        ];
    }

    private function chunkOverflowPages(array $overflowBySection, array $sections): array
    {
        $pageCount = 0;

        foreach ($sections as $section => $config) {
            $capacity = $config['annex_x_capacity'];
            $rows = $overflowBySection[$section] ?? [];
            $sectionPages = $capacity > 0 ? (int) ceil(count($rows) / $capacity) : 0;
            $pageCount = max($pageCount, $sectionPages);
        }

        $pages = [];

        for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {
            $page = [];

            foreach ($sections as $section => $config) {
                $capacity = $config['annex_x_capacity'];
                $offset = $pageIndex * $capacity;
                $page[$section] = array_slice($overflowBySection[$section] ?? [], $offset, $capacity);
            }

            $pages[] = $page;
        }

        return $pages;
    }

    private function buildAnnexAFieldMap(SalnForm $salnForm, array $partitioned, int $totalPages): array
    {
        $declarant = is_array($salnForm->declarant) ? $salnForm->declarant : [];
        $spouse = is_array($salnForm->spouse) ? $salnForm->spouse : [];

        $realProperties = $partitioned['all_sections']['real_properties'];
        $personalProperties = $partitioned['all_sections']['personal_properties'];
        $liabilities = $partitioned['all_sections']['liabilities'];
        $businessInterests = $partitioned['all_sections']['business_interests'];
        $additionalSpouses = $this->normalizeRows($salnForm->additional_spouses ?? [], 2);

        $fieldMap = [
            'ctype_assumption' => $this->checkboxValue($salnForm->compliance_type === 'assumption', 'Yes_abam'),
            'ctype_annual' => $this->checkboxValue($salnForm->compliance_type === 'annual', 'Yes_okpz'),
            'ctype_exit' => $this->checkboxValue($salnForm->compliance_type === 'exit', 'Yes_cen'),
            'assumption_date' => $salnForm->compliance_type === 'assumption' ? $this->formatDate($salnForm->compliance_date?->format('Y-m-d')) : '',
            'annual_year' => $salnForm->compliance_type === 'annual' ? (string) ($salnForm->compliance_year ?? '') : '',
            'exit_date' => $salnForm->compliance_type === 'exit' ? $this->formatDate($salnForm->compliance_date?->format('Y-m-d')) : '',
            'declarant_family_name' => (string) ($declarant['family_name'] ?? ''),
            'declarant_first_name' => (string) ($declarant['first_name'] ?? ''),
            'declarant_middle_initial' => (string) ($declarant['middle_initial'] ?? ''),
            'spouse_family_name' => (string) ($spouse['family_name'] ?? ''),
            'spouse_first_name' => (string) ($spouse['first_name'] ?? ''),
            'spouse_middle_initial' => (string) ($spouse['middle_initial'] ?? ''),
            'declarant_position' => (string) ($declarant['position'] ?? ''),
            'declarant_agency_office' => (string) ($declarant['agency_office'] ?? ''),
            'declarant_office_address' => (string) ($declarant['office_address'] ?? ''),
            'spouse_position' => (string) ($spouse['position'] ?? ''),
            'spouse_agency_office' => (string) ($spouse['agency_office'] ?? ''),
            'spouse_office_address' => (string) ($spouse['office_address'] ?? ''),
            'ftype_joint' => $this->checkboxValue($salnForm->filing_type === 'joint'),
            'ftype_separate' => $this->checkboxValue($salnForm->filing_type === 'separate'),
            'ftype_not_applicable' => $this->checkboxValue($salnForm->filing_type === 'not_applicable'),
            'no_addtl_spouses' => $this->checkboxValue(empty($salnForm->additional_spouses ?? [])),
            'addtl_spouse_1' => (string) (($additionalSpouses[0]['name'] ?? '') ?: ''),
            'addtl_spouse_2' => (string) (($additionalSpouses[1]['name'] ?? '') ?: ''),
            'has_bifc' => $this->checkboxValue(empty($businessInterests)),
            'has_relatives' => $this->checkboxValue(empty($salnForm->relatives_in_government_service ?? [])),
            'rp_subtotal' => $this->formatMoney($this->sumRows($realProperties, 'acquisition_cost')),
            'pp_subtotal' => $this->formatMoney($this->sumRows($personalProperties, 'acquisition_cost_amount')),
            'assets_total' => $this->formatMoney($this->sumRows($realProperties, 'acquisition_cost') + $this->sumRows($personalProperties, 'acquisition_cost_amount')),
            'lb_total' => $this->formatMoney($this->sumRows($liabilities, 'outstanding_balance')),
            'net_worth' => $this->formatMoney(
                ($this->sumRows($realProperties, 'acquisition_cost') + $this->sumRows($personalProperties, 'acquisition_cost_amount'))
                - $this->sumRows($liabilities, 'outstanding_balance')
            ),
            'total_page_a_1' => (string) $totalPages,
            'total_page_a_2' => (string) $totalPages,
        ];

        $children = $this->childrenBelowEighteen($salnForm->children ?? []);
        for ($i = 1; $i <= 3; $i++) {
            $child = $children[$i - 1] ?? null;
            $fieldMap['child_'.$i] = (string) ($child['name'] ?? '');
            $fieldMap['child_'.$i.'_age'] = $child ? (string) $child['age'] : '';
        }

        $this->mapAnnexARealProperties($fieldMap, $partitioned['annex_a']['real_properties']);
        $this->mapAnnexAPersonalProperties($fieldMap, $partitioned['annex_a']['personal_properties']);
        $this->mapAnnexALiabilities($fieldMap, $partitioned['annex_a']['liabilities']);
        $this->mapAnnexABusinessInterests($fieldMap, $partitioned['annex_a']['business_interests']);
        $this->mapAnnexARelatives($fieldMap, $this->normalizeRows($salnForm->relatives_in_government_service ?? [], 5));

        return $fieldMap;
    }

    private function buildAnnexBFieldMap(SalnForm $salnForm, array $pageData, int $pageNo, int $pageTotal): array
    {
        $declarant = is_array($salnForm->declarant) ? $salnForm->declarant : [];
        $realSubtotal = $this->sumRows($pageData['real_properties'] ?? [], 'acquisition_cost');
        $personalSubtotal = $this->sumRows($pageData['personal_properties'] ?? [], 'acquisition_cost_amount');

        $fieldMap = [
            'xb_as_of_date' => $this->asOfDate($salnForm),
            'xb_declarant_family_name' => (string) ($declarant['family_name'] ?? ''),
            'xb_declarant_first_name' => (string) ($declarant['first_name'] ?? ''),
            'xb_declarant_middle_initial' => (string) ($declarant['middle_initial'] ?? ''),
            'xb_declarant_position' => (string) ($declarant['position'] ?? ''),
            'xb_declarant_agency' => (string) ($declarant['agency_office'] ?? ''),
            'xb_rp_subtotal' => $this->formatMoney($realSubtotal),
            'xb_pp_subtotal' => $this->formatMoney($personalSubtotal),
            'xb_assets_total' => $this->formatMoney($realSubtotal + $personalSubtotal),
            'xb_lb_total' => $this->formatMoney($this->sumRows($pageData['liabilities'] ?? [], 'outstanding_balance')),
            'xb_page_no' => (string) $pageNo,
            'xb_page_total' => (string) $pageTotal,
        ];

        $this->mapAnnexXRealProperties($fieldMap, $pageData['real_properties'] ?? [], 'xb_');
        $this->mapAnnexXPersonalProperties($fieldMap, $pageData['personal_properties'] ?? [], 'xb_');
        $this->mapAnnexXLiabilities($fieldMap, $pageData['liabilities'] ?? [], 'xb_');
        $this->mapAnnexXBusinessInterests($fieldMap, $pageData['business_interests'] ?? [], 'xb_');

        return $fieldMap;
    }

    private function buildAnnexCFieldMap(SalnForm $salnForm, array $pageData, int $pageNo, int $pageTotal): array
    {
        $declarant = is_array($salnForm->declarant) ? $salnForm->declarant : [];

        $fieldMap = [
            'xc_as_of_date' => $this->asOfDate($salnForm),
            'xc_declarant_family_name' => (string) ($declarant['family_name'] ?? ''),
            'xc_declarant_first_name' => (string) ($declarant['first_name'] ?? ''),
            'xc_declarant_middle_initial' => (string) ($declarant['middle_initial'] ?? ''),
            'xc_declarant_position' => (string) ($declarant['position'] ?? ''),
            'xc_declarant_agency' => (string) ($declarant['agency_office'] ?? ''),
            'xc_page_no' => (string) $pageNo,
            'xc_total_page' => (string) $pageTotal,
        ];

        $this->mapAnnexXRealProperties($fieldMap, $pageData['real_properties'] ?? [], 'xc_');
        $this->mapAnnexXPersonalProperties($fieldMap, $pageData['personal_properties'] ?? [], 'xc_');
        $this->mapAnnexXLiabilities($fieldMap, $pageData['liabilities'] ?? [], 'xc_');
        $this->mapAnnexXBusinessInterests($fieldMap, $pageData['business_interests'] ?? [], 'xc_');

        return $fieldMap;
    }

    private function fillAndFlatten(string $templatePath, array $fieldMap, string $outputPath, string $tmpPrefix): void
    {
        $fdfPath = $tmpPrefix.'.fdf';
        $this->writeFdf($fdfPath, $fieldMap);

        $this->runPdftk([
            $templatePath,
            'fill_form',
            $fdfPath,
            'output',
            $outputPath,
            'flatten',
        ]);
    }

    private function writeFdf(string $path, array $fieldMap): void
    {
        $fields = '';

        foreach ($fieldMap as $name => $value) {
            $fields .= "<< /T (".$this->escapePdfString((string) $name).") /V (".$this->escapePdfString((string) $value).") >>\n";
        }

        $fdf = "%FDF-1.2\n";
        $fdf .= "1 0 obj\n";
        $fdf .= "<< /FDF << /Fields [\n".$fields."] >> >>\n";
        $fdf .= "endobj\n";
        $fdf .= "trailer\n";
        $fdf .= "<< /Root 1 0 R >>\n";
        $fdf .= "%%EOF\n";

        if (file_put_contents($path, $fdf) === false) {
            throw new RuntimeException('PDF generation failed.');
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException('PDF generation failed.');
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }

    private function runPdftk(array $args): void
    {
        $binary = config('services.pdftk.binary', 'pdftk');
        $command = array_merge([$binary], $args);

        $process = new Process($command);
        $process->setTimeout(60);

        logger()->info('Running pdftk command', [
            'binary' => $binary,
            'args' => $args,
            'binary_exists' => is_file($binary),
            'binary_executable' => is_executable($binary),
        ]);

        $process->run();

        if (! $process->isSuccessful()) {
            logger()->error('pdftk command failed', [
                'command' => $command,
                'exit_code' => $process->getExitCode(),
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput(),
            ]);
            throw new RuntimeException('PDF generation failed.');
        }
    }

    private function normalizeRows(array $rows, ?int $limit = null): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if ($this->isRowEmpty($row)) {
                continue;
            }

            $scope = $row['owner_scope'] ?? self::OWNER_DECLARANT;
            $row['owner_scope'] = in_array($scope, [self::OWNER_DECLARANT, self::OWNER_SPOUSE_CHILDREN], true)
                ? $scope
                : self::OWNER_DECLARANT;

            $normalized[] = $row;
        }

        if ($limit !== null) {
            $normalized = array_slice($normalized, 0, $limit);
        }

        return array_values($normalized);
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $key => $value) {
            if ($key === 'owner_scope') {
                continue;
            }

            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    private function childrenBelowEighteen(array $children): array
    {
        $output = [];

        foreach ($children as $child) {
            if (! is_array($child)) {
                continue;
            }

            $name = trim((string) ($child['name'] ?? ''));
            $birthDate = (string) ($child['date_of_birth'] ?? '');
            $age = $this->calculateAge($birthDate);

            if ($name === '' || $age === null || $age >= 18) {
                continue;
            }

            $output[] = [
                'name' => $name,
                'age' => $age,
            ];

            if (count($output) === 3) {
                break;
            }
        }

        return $output;
    }

    private function calculateAge(string $birthDate): ?int
    {
        if ($birthDate === '') {
            return null;
        }

        try {
            $dob = CarbonImmutable::parse($birthDate)->startOfDay();
            $today = CarbonImmutable::now()->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($dob->greaterThan($today)) {
            return null;
        }

        return $dob->diffInYears($today);
    }

    private function mapAnnexARealProperties(array &$fieldMap, array $rows): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $row = $rows[$i - 1] ?? [];
            $fieldMap['rp_description_'.$i] = (string) ($row['description'] ?? '');
            $fieldMap['rp_kind_'.$i] = (string) ($row['kind'] ?? '');
            $fieldMap['rp_exact_location_'.$i] = (string) ($row['exact_location'] ?? '');
            $fieldMap['rp_mode_of_acquisition_'.$i] = (string) ($row['mode_of_acquisition'] ?? '');
            $fieldMap['rp_year_of_acquisition_'.$i] = (string) ($row['year_of_acquisition'] ?? '');
            $fieldMap['rp_acquisition_cost_'.$i] = $this->formatMoney($row['acquisition_cost'] ?? null);
            $fieldMap['rp_assessed_value_'.$i] = $this->formatMoney($row['assessed_value'] ?? null);
            $fieldMap['rp_current_fair_market_value_'.$i] = $this->formatMoney($row['current_fair_market_value'] ?? null);
        }
    }

    private function mapAnnexAPersonalProperties(array &$fieldMap, array $rows): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $row = $rows[$i - 1] ?? [];
            $fieldMap['pp_description_'.$i] = (string) ($row['description'] ?? '');
            $fieldMap['pp_acquisition_year_'.$i] = (string) ($row['acquisition_year'] ?? '');
            $fieldMap['pp_acquisition_cost_amount_'.$i] = $this->formatMoney($row['acquisition_cost_amount'] ?? null);
        }
    }

    private function mapAnnexALiabilities(array &$fieldMap, array $rows): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $row = $rows[$i - 1] ?? [];
            $fieldMap['lb_nature_'.$i] = (string) ($row['nature'] ?? '');
            $fieldMap['lb_name_of_creditor_'.$i] = (string) ($row['name_of_creditor'] ?? '');
            $fieldMap['lb_outstanding_balance_'.$i] = $this->formatMoney($row['outstanding_balance'] ?? null);
        }
    }

    private function mapAnnexABusinessInterests(array &$fieldMap, array $rows): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $row = $rows[$i - 1] ?? [];
            $fieldMap['bi_name_of_entity_'.$i] = (string) ($row['name_of_entity_or_business_enterprise'] ?? '');
            $fieldMap['bi_business_address_'.$i] = (string) ($row['business_address'] ?? '');
            $fieldMap['bi_nature_'.$i] = (string) ($row['nature_of_business_interest_or_financial_connection'] ?? '');
            $fieldMap['bi_acquisition_date_'.$i] = $this->formatDate($row['date_of_acquisition'] ?? null);
        }
    }

    private function mapAnnexARelatives(array &$fieldMap, array $rows): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $row = $rows[$i - 1] ?? [];
            $fieldMap['relatives_name_'.$i] = (string) ($row['name_of_relative'] ?? '');
            $fieldMap['relatives_relationship_'.$i] = (string) ($row['relationship'] ?? '');
            $fieldMap['relatives_position_'.$i] = (string) ($row['position'] ?? '');
            $fieldMap['relatives_agency_name_address_'.$i] = (string) ($row['name_of_agency_office_and_address'] ?? '');
        }
    }

    private function mapAnnexXRealProperties(array &$fieldMap, array $rows, string $prefix): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $row = $rows[$i - 1] ?? [];
            $fieldMap[$prefix.'rp_description_'.$i] = (string) ($row['description'] ?? '');
            $fieldMap[$prefix.'rp_kind_'.$i] = (string) ($row['kind'] ?? '');
            $fieldMap[$prefix.'rp_location_'.$i] = (string) ($row['exact_location'] ?? '');
            $fieldMap[$prefix.'rp_acquisition_mode_'.$i] = (string) ($row['mode_of_acquisition'] ?? '');
            $fieldMap[$prefix.'rp_assessed_value_'.$i] = $this->formatMoney($row['assessed_value'] ?? null);
            $fieldMap[$prefix.'rp_cfm_value_'.$i] = $this->formatMoney($row['current_fair_market_value'] ?? null);
            $fieldMap[$prefix.'rp_acquisition_cost_'.$i] = $this->formatMoney($row['acquisition_cost'] ?? null);
            $fieldMap[$prefix.'rp_acquisition_year_'.$i] = (string) ($row['year_of_acquisition'] ?? '');
        }
    }

    private function mapAnnexXPersonalProperties(array &$fieldMap, array $rows, string $prefix): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $row = $rows[$i - 1] ?? [];
            $fieldMap[$prefix.'pp_description_'.$i] = (string) ($row['description'] ?? '');
            $fieldMap[$prefix.'pp_acquisition_year_'.$i] = (string) ($row['acquisition_year'] ?? '');
            $fieldMap[$prefix.'pp_acquisition_cost_'.$i] = $this->formatMoney($row['acquisition_cost_amount'] ?? null);
        }
    }

    private function mapAnnexXLiabilities(array &$fieldMap, array $rows, string $prefix): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $row = $rows[$i - 1] ?? [];
            $fieldMap[$prefix.'lb_nature_'.$i] = (string) ($row['nature'] ?? '');
            $fieldMap[$prefix.'lb_creditor_'.$i] = (string) ($row['name_of_creditor'] ?? '');
            $fieldMap[$prefix.'lb_outstanding_balance_'.$i] = $this->formatMoney($row['outstanding_balance'] ?? null);
        }
    }

    private function mapAnnexXBusinessInterests(array &$fieldMap, array $rows, string $prefix): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $row = $rows[$i - 1] ?? [];
            $fieldMap[$prefix.'bi_entity_name_'.$i] = (string) ($row['name_of_entity_or_business_enterprise'] ?? '');
            $fieldMap[$prefix.'bi_business_address_'.$i] = (string) ($row['business_address'] ?? '');
            $fieldMap[$prefix.'bi_address_'.$i] = (string) ($row['business_address'] ?? '');
            $fieldMap[$prefix.'bi_nature_'.$i] = (string) ($row['nature_of_business_interest_or_financial_connection'] ?? '');
            $fieldMap[$prefix.'bi_acquisition_date_'.$i] = $this->formatDate($row['date_of_acquisition'] ?? null);
        }
    }

    private function checkboxValue(bool $checked, string $onValue = 'Yes_abam'): string
    {
        return $checked ? $onValue : 'Off';
    }

    private function sumRows(array $rows, string $field): float
    {
        $sum = 0.0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $sum += (float) ($row[$field] ?? 0);
        }

        return $sum;
    }

    private function formatMoney(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function formatDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        try {
            return CarbonImmutable::parse($date)->format('d/m/Y');
        } catch (\Throwable) {
            return '';
        }
    }

    private function asOfDate(SalnForm $salnForm): string
    {
        if ($salnForm->compliance_type === 'annual' && $salnForm->compliance_year) {
            return '31/12/'.(string) $salnForm->compliance_year;
        }

        return $this->formatDate($salnForm->compliance_date?->format('Y-m-d'));
    }

    private function escapePdfString(string $value): string
    {
        return str_replace(
            ["\\", "(", ")", "\r", "\n"],
            ["\\\\", "\\(", "\\)", '', ' '],
            $value
        );
    }
}
